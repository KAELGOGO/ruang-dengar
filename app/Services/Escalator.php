<?php

namespace App\Services;

use App\Models\AvailabilitySlot;
use App\Models\CounselingSession;
use App\Models\Escalation;
use App\Models\ListenerProfile;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Module 4 owns this class. Only the listener escalates ("Oper ke psikolog"): crisis keywords
 * in the chat are an alarm for the listener (Module 3) and never escalate by themselves.
 *
 * The psychologist never joins the old chat (a session has exactly two participants).
 * Instead the user gets a NEW session with the psychologist; the old one stays open so the
 * listener can keep the user company until the hand-over.
 */
class Escalator
{
    /**
     * Listener presses "Oper ke psikolog" (flowchart: "Further action?").
     * Returns the escalation: assigned when a free psychologist was paired right away, else open (queue).
     */
    public function byListener(CounselingSession $session, User $listener, string $reason): Escalation
    {
        // Two tries, like ListenerMatcher::book(): if another escalation grabbed the same
        // psychologist slot first, the unique index on availability_slot_id rejects ours.
        for ($try = 0; $try < 2; $try++) {
            try {
                return DB::transaction(fn () => $this->createEscalation($session, $listener, $reason, true));
            } catch (UniqueConstraintViolationException) {
                // The same form sent twice: the case already exists, so return it.
                if ($existing = $session->escalation()->first()) {
                    return $existing;
                }
            }
        }

        // Lost the slot race twice: put the case in the queue instead.
        return DB::transaction(fn () => $this->createEscalation($session, $listener, $reason, false));
    }

    /**
     * "Ambil kasus" from the queue. Returns the new session, or null when another
     * psychologist took the case first.
     */
    public function take(Escalation $escalation, User $psychologist): ?CounselingSession
    {
        return DB::transaction(function () use ($escalation, $psychologist) {
            // Conditional update: only one of two psychologists clicking at once gets 1 row.
            $taken = Escalation::whereKey($escalation->id)
                ->where('status', Escalation::OPEN)
                ->update(['psychologist_id' => $psychologist->id, 'status' => Escalation::ASSIGNED]);
            if ($taken === 0) {
                return null;
            }

            // Reuse the psychologist's own free slot running now, else open one for the next hour.
            $slot = $psychologist->availabilitySlots()
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>', now())
                ->whereDoesntHave('counselingSession')
                ->lockForUpdate()
                ->first()
                ?? $psychologist->availabilitySlots()->create(['starts_at' => now(), 'ends_at' => now()->addHour()]);

            return $this->openSession($escalation->counselingSession, $slot);
        });
    }

    private function createEscalation(CounselingSession $session, User $listener, string $reason, bool $pairNow): Escalation
    {
        $escalation = new Escalation(['source' => 'listener', 'reason' => $reason]);
        $escalation->forceFill([
            'counseling_session_id' => $session->id,
            'raised_by' => $listener->id,
            'status' => Escalation::OPEN,
        ])->save();

        $slot = $pairNow ? $this->freePsychologistSlot($session) : null;
        if ($slot) {
            $this->openSession($session, $slot);
            $escalation->forceFill(['psychologist_id' => $slot->listener_id, 'status' => Escalation::ASSIGNED])->save();
        }

        // The old session's status is NOT changed: "escalated" would lock its chat.
        return $escalation;
    }

    /** A psychologist (not the session's own listener) with an unbooked slot running now. */
    private function freePsychologistSlot(CounselingSession $session): ?AvailabilitySlot
    {
        return app(ListenerMatcher::class)->openSlots($session->user, now())
            ->whereHas('listener.listenerProfile', fn ($p) => $p->where('credential_level', ListenerProfile::PSYCHOLOGIST))
            ->where('listener_id', '!=', $session->listener_id)
            ->lockForUpdate()
            ->first();
    }

    /** New session for the same user and topic, with the slot's psychologist, starting now. */
    private function openSession(CounselingSession $old, AvailabilitySlot $slot): CounselingSession
    {
        $session = new CounselingSession(['topic' => $old->topic, 'scheduled_at' => now()]);
        $session->forceFill([
            'user_id' => $old->user_id,
            'listener_id' => $slot->listener_id,
            'availability_slot_id' => $slot->id,
            'status' => CounselingSession::SCHEDULED,
        ])->save();

        return $session;
    }
}

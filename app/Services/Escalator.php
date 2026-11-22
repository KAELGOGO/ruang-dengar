<?php

namespace App\Services;

use App\Models\CounselingSession;
use App\Models\Escalation;
use App\Models\User;

/**
 * Module 4 owns this class. Only the listener escalates ("Oper ke psikolog"): crisis keywords
 * in the chat are an alarm for the listener (Module 3) and never escalate by themselves.
 */
class Escalator
{
    /**
     * Listener presses "Oper ke psikolog" (flowchart: "Further action?").
     * The old session stays open: the listener keeps the user company until a psychologist takes over.
     */
    public function byListener(CounselingSession $session, User $listener, string $reason): Escalation
    {
        // TODO (Module 4):
        // 1. Create the escalation: source listener, raised_by = listener, status open.
        // 2. Look for a psychologist with an open slot right now:
        //    app(ListenerMatcher::class)->openSlots($session->user, now())
        //        ->whereHas('listener.listenerProfile', fn ($p) => $p->where('credential_level', \App\Models\ListenerProfile::PSYCHOLOGIST))
        //        ->where('listener_id', '!=', $session->listener_id)
        //        ->first();
        // 3. Found: create a new CounselingSession on that slot for the same user and topic, with the
        //    psychologist as listener_id, then set psychologist_id and status assigned. Copy the
        //    transaction + lockForUpdate + retry on UniqueConstraintViolationException from
        //    ListenerMatcher::book(), so two escalations at once cannot take the same slot.
        //    Not found: leave it open, it waits in the queue (EscalationController@take).
        // 4. Do not change the old session's status (an escalated status would lock the chat).
        throw new \LogicException('Belum dibuat (Modul 4)');
    }
}

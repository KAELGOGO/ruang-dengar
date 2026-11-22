<?php

namespace App\Services;

use App\Models\AvailabilitySlot;
use App\Models\CounselingSession;
use App\Models\ListenerProfile;
use App\Models\User;
use App\Support\AgeTier;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Matching rule (team decision, Nov 2026; priority order agreed 22 Nov):
 * - only admin-approved listeners;
 * - teens (13-17) are matched with psychologists only, because listeners must be at least 18;
 * - users 18+ get, in this order: a volunteer/psychology student of their own age tier, one of
 *   another tier, and a psychologist only as the last resort (psychologists are few and also
 *   take the escalated cases);
 * - within the same rank, the listener with the fewest sessions goes first.
 */
class ListenerMatcher
{
    public function openSlots(User $user, CarbonInterface $at): Builder
    {
        $tier = $user->ageTier();
        [$earliest, $latest] = AgeTier::birthDateRange($tier, $at);

        $load = CounselingSession::selectRaw('count(*)')
            ->whereColumn('counseling_sessions.listener_id', 'availability_slots.listener_id')
            ->where('status', '!=', CounselingSession::CANCELLED);
        $isPsychologist = ListenerProfile::selectRaw('count(*)')
            ->whereColumn('listener_profiles.user_id', 'availability_slots.listener_id')
            ->where('credential_level', ListenerProfile::PSYCHOLOGIST);
        // Peers only: psychologists are one rank, ordered by load alone.
        // whereDate: the date cast stores "Y-m-d 00:00:00", which a plain string compare gets wrong on birthdays.
        $sameTier = User::selectRaw('count(*)')
            ->whereColumn('users.id', 'availability_slots.listener_id')
            ->whereHas('listenerProfile', fn (Builder $p) => $p->where('credential_level', '!=', ListenerProfile::PSYCHOLOGIST))
            ->whereDate('birth_date', '<=', $latest)
            ->when($earliest, fn (Builder $q) => $q->whereDate('birth_date', '>=', $earliest));

        return AvailabilitySlot::query()
            ->where('starts_at', '<=', $at)
            ->where('ends_at', '>', $at)
            ->whereDoesntHave('counselingSession')
            ->where('listener_id', '!=', $user->id)
            ->whereHas('listener', function (Builder $listener) use ($tier) {
                $listener->where('role', User::ROLE_LISTENER)
                    ->whereHas('listenerProfile', function (Builder $p) use ($tier) {
                        $p->where('verification_status', ListenerProfile::APPROVED);
                        if ($tier === AgeTier::TEEN) {
                            $p->where('credential_level', ListenerProfile::PSYCHOLOGIST);
                        }
                    });
            })
            ->addSelect(['listener_load' => $load, 'is_psychologist' => $isPsychologist, 'same_tier' => $sameTier])
            ->orderBy('is_psychologist')
            ->orderByDesc('same_tier')
            ->orderBy('listener_load')
            ->orderBy('starts_at');
    }

    /** Books the best open slot at $at, or returns null when nobody is available. */
    public function book(User $user, string $topic, CarbonInterface $at): ?CounselingSession
    {
        if (! $user->ageTier()) {
            return null;
        }

        // Two tries: if another request grabbed the same slot first, the unique index on
        // availability_slot_id rejects ours and the second try picks the next slot.
        for ($try = 0; $try < 2; $try++) {
            try {
                return DB::transaction(function () use ($user, $topic, $at) {
                    $slot = $this->openSlots($user, $at)->lockForUpdate()->first();
                    if (! $slot) {
                        return null;
                    }

                    $session = new CounselingSession(['topic' => $topic, 'scheduled_at' => $at]);
                    $session->forceFill([
                        'user_id' => $user->id,
                        'listener_id' => $slot->listener_id,
                        'availability_slot_id' => $slot->id,
                        'status' => CounselingSession::SCHEDULED,
                    ])->save();

                    return $session;
                });
            } catch (UniqueConstraintViolationException) {
                continue;
            }
        }

        return null;
    }
}

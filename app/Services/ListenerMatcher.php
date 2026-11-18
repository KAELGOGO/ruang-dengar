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
 * Matching rule (team decision, Nov 2026):
 * - only admin-approved listeners;
 * - volunteers and psychology students serve users in their own age tier;
 * - psychologists may serve every tier, so teens (13-17) are only matched with psychologists,
 *   because listeners must be at least 18.
 * Among eligible slots, the listener with the fewest sessions goes first.
 */
class ListenerMatcher
{
    public function openSlots(User $user, CarbonInterface $at): Builder
    {
        [$earliest, $latest] = AgeTier::birthDateRange($user->ageTier(), $at);

        $load = CounselingSession::selectRaw('count(*)')
            ->whereColumn('counseling_sessions.listener_id', 'availability_slots.listener_id')
            ->where('status', '!=', CounselingSession::CANCELLED);

        return AvailabilitySlot::query()
            ->where('starts_at', '<=', $at)
            ->where('ends_at', '>', $at)
            ->whereDoesntHave('counselingSession')
            ->where('listener_id', '!=', $user->id)
            ->whereHas('listener', function (Builder $listener) use ($earliest, $latest) {
                $listener->where('role', User::ROLE_LISTENER)
                    ->whereHas('listenerProfile', fn (Builder $p) => $p->where('verification_status', ListenerProfile::APPROVED))
                    ->where(function (Builder $q) use ($earliest, $latest) {
                        $q->whereHas('listenerProfile', fn (Builder $p) => $p->where('credential_level', ListenerProfile::PSYCHOLOGIST))
                            ->orWhere(function (Builder $peer) use ($earliest, $latest) {
                                $peer->where('birth_date', '<=', $latest);
                                if ($earliest) {
                                    $peer->where('birth_date', '>=', $earliest);
                                }
                            });
                    });
            })
            ->addSelect(['listener_load' => $load])
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

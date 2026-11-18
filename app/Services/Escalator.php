<?php

namespace App\Services;

use App\Models\CounselingSession;
use App\Models\Escalation;
use App\Models\User;

/**
 * Module 4 owns this class. Module 3 (chat) calls byKeyword() when a flagged message is
 * saved, so both modules agree on these two signatures from day one.
 */
class Escalator
{
    /** Listener presses "Perlu tindakan lanjut?" (flowchart). */
    public function byListener(CounselingSession $session, User $listener, string $reason): Escalation
    {
        // TODO (Module 4): create the escalation (source listener, raised_by = listener),
        // set the session status to escalated, return it.
        throw new \LogicException('Belum dibuat (Modul 4)');
    }

    /** A chat message matched config('krisis.kata_kunci'). Must not throw: chat keeps working. */
    public function byKeyword(CounselingSession $session, string $reason): ?Escalation
    {
        // TODO (Module 4): if the session has no escalation yet, create one with
        // source keyword (raised_by null) so it shows up first in the psychologist queue.
        return null;
    }
}

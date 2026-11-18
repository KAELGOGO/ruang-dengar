<?php

namespace App\Policies;

use App\Models\CounselingSession;
use App\Models\User;

/**
 * Who may touch a session. Admins are deliberately NOT allowed to read chats:
 * chat content is sensitive, and the proposal only needs counts for the metrics.
 */
class CounselingSessionPolicy
{
    /** Read the chat: the two participants, or the psychologist handling its escalation. */
    public function view(User $user, CounselingSession $session): bool
    {
        return $session->hasParticipant($user)
            || ($user->isPsychologist() && $session->escalation?->psychologist_id === $user->id);
    }

    /** Send messages: participants only, while the session is still open. */
    public function chat(User $user, CounselingSession $session): bool
    {
        return $session->hasParticipant($user)
            && in_array($session->status, [CounselingSession::SCHEDULED, CounselingSession::ACTIVE], true);
    }

    public function end(User $user, CounselingSession $session): bool
    {
        return $this->chat($user, $session);
    }

    public function cancel(User $user, CounselingSession $session): bool
    {
        return $session->hasParticipant($user) && $session->status === CounselingSession::SCHEDULED;
    }

    /** "Further action?" is the listener's decision (flowchart). */
    public function escalate(User $user, CounselingSession $session): bool
    {
        return $user->id === $session->listener_id && $session->escalation === null;
    }

    public function rate(User $user, CounselingSession $session): bool
    {
        return $user->id === $session->user_id
            && in_array($session->status, [CounselingSession::ENDED, CounselingSession::ESCALATED], true)
            && $session->rating === null;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\CounselingSession;
use Illuminate\Support\Facades\Gate;

// Module 2: list and cancel sessions (user and listener).
class SessionController extends Controller
{
    private const UPCOMING = [CounselingSession::SCHEDULED, CounselingSession::ACTIVE];

    public function index()
    {
        $user = auth()->user();

        // escalation and rating are read by the policy checks in the view, so load them up front (no N+1).
        $sessions = CounselingSession::with(['user', 'listener.listenerProfile', 'escalation', 'rating'])
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('listener_id', $user->id))
            ->latest('scheduled_at')
            ->get();

        [$upcoming, $history] = $sessions->partition(fn ($session) => in_array($session->status, self::UPCOMING, true));

        return view('sessions.index', [
            'upcoming' => $upcoming->sortBy('scheduled_at'),
            'history' => $history,
        ]);
    }

    public function cancel(CounselingSession $session)
    {
        Gate::authorize('cancel', $session);

        // Releasing the slot (availability_slot_id = null) makes it bookable again.
        $session->forceFill(['status' => CounselingSession::CANCELLED, 'availability_slot_id' => null])->save();

        return back()->with('status', 'Sesi dibatalkan.');
    }
}

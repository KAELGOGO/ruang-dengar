<?php

namespace App\Http\Controllers;

use App\Models\CounselingSession;
use Illuminate\Support\Facades\Gate;

// Module 2: list and cancel sessions (user and listener).
class SessionController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $sessions = CounselingSession::with(['user', 'listener'])
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('listener_id', $user->id))
            ->latest('scheduled_at')
            ->get();

        return view('sessions.index', compact('sessions'));
    }

    public function cancel(CounselingSession $session)
    {
        Gate::authorize('cancel', $session);

        // TODO (Module 2): confirm dialog in the view; notify the other participant if needed.
        $session->forceFill(['status' => CounselingSession::CANCELLED, 'availability_slot_id' => null])->save();

        return back()->with('status', 'Sesi dibatalkan.');
    }
}

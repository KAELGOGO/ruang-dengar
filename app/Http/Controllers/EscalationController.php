<?php

namespace App\Http\Controllers;

use App\Models\CounselingSession;
use App\Models\Escalation;
use App\Services\Escalator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

// Module 4: escalation to a psychologist ("Further action?" in the flowchart).
class EscalationController extends Controller
{
    public function store(Request $request, CounselingSession $session)
    {
        Gate::authorize('escalate', $session);
        abort_if($session->status === CounselingSession::CANCELLED, 403, 'Sesi ini sudah dibatalkan.');
        // A psychologist already is the professional (e.g. the new session after a hand-over).
        abort_if($request->user()->isPsychologist(), 403, 'Kamu sudah psikolog, jadi tidak perlu mengoper kasus.');
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $escalation = app(Escalator::class)->byListener($session, $request->user(), $data['reason']);

        $pesan = $escalation->status === Escalation::ASSIGNED
            ? 'Kasus dioper. '.$escalation->psychologist->name.' sudah dipasangkan dengan pengguna di sesi baru. Kamu boleh berpamitan lalu mengakhiri sesi ini.'
            : 'Kasus masuk antrean psikolog. Tetap temani pengguna sampai ada psikolog yang mengambil kasusnya.';

        return back()->with('status', $pesan);
    }

    public function index(Request $request)
    {
        $me = $request->user();

        // Open cases for everyone, assigned cases only for the psychologist handling them
        // (the reason is sensitive, other psychologists do not need it).
        $escalations = Escalation::with(['counselingSession.user', 'counselingSession.listener.listenerProfile', 'raisedBy'])
            ->where(fn ($q) => $q->where('status', Escalation::OPEN)
                ->orWhere(fn ($q) => $q->where('status', Escalation::ASSIGNED)->where('psychologist_id', $me->id)))
            ->orderByRaw("case when status = 'open' then 0 else 1 end")
            ->oldest()
            ->get();

        return view('escalations.index', ['escalations' => $escalations]);
    }

    public function take(Request $request, Escalation $escalation)
    {
        // Taking your own session's case would hand the user over to yourself.
        abort_if($escalation->counselingSession->listener_id === $request->user()->id, 403, 'Ini kasus dari sesimu sendiri.');

        $session = app(Escalator::class)->take($escalation, $request->user());
        if (! $session) {
            return back()->with('status', 'Kasus sudah diambil psikolog lain.');
        }

        return redirect()->route('chat.show', $session)
            ->with('status', 'Kasus diambil. Ini sesi barumu dengan pengguna. Riwayat chat sebelumnya bisa dibaca dari Antrean eskalasi.');
    }

    public function resolve(Request $request, Escalation $escalation)
    {
        abort_unless($escalation->psychologist_id === $request->user()->id, 403);

        $escalation->forceFill(['status' => Escalation::RESOLVED, 'resolved_at' => now()])->save();

        return back()->with('status', 'Kasus ditandai selesai.');
    }
}

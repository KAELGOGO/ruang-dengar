<?php

namespace App\Http\Controllers;

use App\Models\CounselingSession;
use App\Models\Escalation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

// Module 4: escalation to a psychologist ("Further action?" in the flowchart).
class EscalationController extends Controller
{
    public function store(Request $request, CounselingSession $session)
    {
        Gate::authorize('escalate', $session);
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        // TODO (Module 4): app(\App\Services\Escalator::class)->byListener($session, $request->user(), ...),
        // then redirect back with a message.
        abort(501, 'Belum dibuat (Modul 4)');
    }

    public function index()
    {
        return view('placeholder', [
            'modul' => 'Modul 4: Eskalasi',
            'judul' => 'Antrean eskalasi (khusus psikolog)',
            'todo' => [
                'Daftar eskalasi: open dulu, yang paling lama menunggu di atas (selama menunggu, pengguna masih ditemani listener).',
                'Tombol "Ambil kasus" (route escalations.take) lalu "Selesai" (escalations.resolve).',
                'Setelah diambil, psikolog itu boleh membaca chat sesi lama sebagai konteks (sudah diatur di CounselingSessionPolicy@view). Sebelum diambil belum boleh.',
            ],
        ]);
    }

    public function take(Escalation $escalation)
    {
        // TODO (Module 4): only if still open, with a conditional update
        // (Escalation::whereKey(...)->where('status', 'open')->update(...)) so two psychologists
        // cannot both take it; set psychologist_id = auth id, status assigned.
        // Then create a new session for the same user and topic with this psychologist starting now:
        // an AvailabilitySlot now..now+60 min and a CounselingSession on it, and redirect to its chat.
        abort(501, 'Belum dibuat (Modul 4)');
    }

    public function resolve(Escalation $escalation)
    {
        // TODO (Module 4): only the assigned psychologist; status resolved + resolved_at.
        abort(501, 'Belum dibuat (Modul 4)');
    }
}

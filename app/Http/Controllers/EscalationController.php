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
                'Daftar eskalasi status open/assigned, terbaru dulu, tandai yang dari kata kunci (source keyword).',
                'Tombol "Ambil kasus" (route escalations.take) lalu "Selesai" (escalations.resolve).',
                'Psikolog yang mengambil kasus boleh membaca chat sesi itu (sudah diatur di CounselingSessionPolicy@view).',
            ],
        ]);
    }

    public function take(Escalation $escalation)
    {
        // TODO (Module 4): only if still open; set psychologist_id = auth id, status assigned.
        abort(501, 'Belum dibuat (Modul 4)');
    }

    public function resolve(Escalation $escalation)
    {
        // TODO (Module 4): only the assigned psychologist; status resolved + resolved_at.
        abort(501, 'Belum dibuat (Modul 4)');
    }
}

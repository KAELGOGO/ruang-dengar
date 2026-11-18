<?php

namespace App\Http\Controllers;

use App\Models\CounselingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

// Module 3: chat. Polling: the page asks GET messages?after=<last id> every 2-3 s.
class ChatController extends Controller
{
    public function show(CounselingSession $session)
    {
        Gate::authorize('view', $session);

        return view('placeholder', [
            'modul' => 'Modul 3: Chat',
            'judul' => 'Ruang chat sesi #'.$session->id,
            'todo' => [
                'Tampilkan pesan + form kirim; JS fetch ke route chat.messages tiap 2-3 detik (parameter after = id pesan terakhir).',
                'Chat hanya aktif di jam jadwal (scheduled_at sampai slot.ends_at); di luar itu form dikunci.',
                'Panel kontak krisis (config/krisis.php) selalu terlihat + tombol "Saya butuh bantuan segera".',
                'Tombol "Akhiri sesi" (route chat.end), lalu user diarahkan ke rating.',
            ],
        ]);
    }

    public function messages(Request $request, CounselingSession $session)
    {
        Gate::authorize('view', $session);

        // TODO (Module 3): return messages with id > after as JSON (id, sender_id, body, created_at).
        return response()->json([]);
    }

    public function store(Request $request, CounselingSession $session)
    {
        Gate::authorize('chat', $session);
        $request->validate(['body' => ['required', 'string', 'max:2000']]);

        // TODO (Module 3): save the message. Set flagged = true when the text contains any
        // config('krisis.kata_kunci') BEFORE saving (the body is encrypted afterwards), and
        // let Module 4 create an escalation with source = 'keyword'.
        abort(501, 'Belum dibuat (Modul 3)');
    }

    public function end(CounselingSession $session)
    {
        Gate::authorize('end', $session);

        // TODO (Module 3): status ended + ended_at, then redirect the user to ratings.create.
        abort(501, 'Belum dibuat (Modul 3)');
    }
}

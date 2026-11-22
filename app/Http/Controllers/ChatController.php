<?php

namespace App\Http\Controllers;

use App\Models\CounselingSession;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

// Module 3: chat. Polling: the page asks GET messages?after=<last id> every 3 s.
class ChatController extends Controller
{
    public function show(Request $request, CounselingSession $session)
    {
        Gate::authorize('view', $session);
        $session->load(['user', 'listener.listenerProfile', 'slot']);

        // null = the form is shown; otherwise the reason it is locked.
        $keterangan = match (true) {
            ! $session->hasParticipant($request->user()) => 'Kamu membuka chat ini sebagai psikolog yang menangani eskalasi, jadi hanya bisa membaca.',
            $session->status === CounselingSession::CANCELLED => 'Sesi ini dibatalkan.',
            $session->status === CounselingSession::ENDED => 'Sesi ini sudah selesai.',
            $session->status === CounselingSession::ESCALATED => 'Sesi ini sudah diteruskan ke psikolog, jadi chat ditutup.',
            now()->lt($session->scheduled_at) => 'Chat dibuka '.$session->scheduled_at->format('d M Y, H:i').'. Muat ulang halaman ini saat jamnya tiba.',
            ! $this->isOpenNow($session) => 'Waktu sesi sudah habis. Tekan "Akhiri sesi" untuk menutupnya.',
            default => null,
        };

        return view('chat.show', [
            'session' => $session,
            'keterangan' => $keterangan,
            'bisaAkhiri' => Gate::allows('end', $session) && now()->gte($session->scheduled_at),
        ]);
    }

    public function messages(Request $request, CounselingSession $session)
    {
        Gate::authorize('view', $session);
        $after = $request->validate(['after' => ['nullable', 'integer', 'min:0']])['after'] ?? 0;

        $pesan = $session->messages()->where('id', '>', $after)->orderBy('id')->get()
            ->map(fn (Message $m) => $this->format($m, $session, $request->user()->id));

        // Lets the chat page show the hand-over to a psychologist without a reload (Module 4 creates it).
        $eskalasi = $session->escalation;

        return response()->json([
            'status' => $session->status,
            'eskalasi' => $eskalasi ? ($eskalasi->psychologist_id ? 'diambil' : 'menunggu') : null,
            'pesan' => $pesan,
        ]);
    }

    public function store(Request $request, CounselingSession $session)
    {
        Gate::authorize('chat', $session);
        abort_unless($this->isOpenNow($session), 403, 'Chat hanya bisa dipakai di jam sesi.');
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $sender = $request->user();

        // Checked BEFORE saving: the body is encrypted afterwards and cannot be searched.
        // The keyword is only an alarm (crisis contacts + badge); the listener decides on escalation.
        $keyword = $this->crisisKeyword($data['body']);

        $message = new Message(['body' => $data['body'], 'flagged' => $keyword !== null]);
        $message->sender()->associate($sender);
        $session->messages()->save($message);

        // Conditional update, so a session ended at the same moment is never reopened.
        CounselingSession::whereKey($session->id)->where('status', CounselingSession::SCHEDULED)
            ->update(['status' => CounselingSession::ACTIVE, 'started_at' => now()]);

        return response()->json([
            'pesan' => $this->format($message, $session, $sender->id),
            'krisis' => $keyword !== null,
        ], 201);
    }

    public function end(Request $request, CounselingSession $session)
    {
        Gate::authorize('end', $session);
        // An ended session counts as completed in the metrics; before the start, "Batalkan" is the right action.
        abort_if(now()->lt($session->scheduled_at), 403, 'Sesi belum dimulai. Pakai "Batalkan" di Sesi saya.');

        // Conditional update, so a session closed at the same moment (e.g. cancelled) is not overwritten.
        CounselingSession::whereKey($session->id)
            ->whereIn('status', [CounselingSession::SCHEDULED, CounselingSession::ACTIVE])
            ->update(['status' => CounselingSession::ENDED, 'ended_at' => now()]);

        return $request->user()->id === $session->user_id
            ? redirect()->route('ratings.create', $session)
            : redirect()->route('sessions.index')->with('status', 'Sesi diakhiri.');
    }

    // Messages are allowed from the booked start until the end of the listener's slot.
    private function isOpenNow(CounselingSession $session): bool
    {
        return $session->slot !== null && now()->between($session->scheduled_at, $session->slot->ends_at);
    }

    private function crisisKeyword(string $text): ?string
    {
        $text = Str::squish(Str::lower($text));

        return collect(config('krisis.kata_kunci'))->first(fn (string $kata) => str_contains($text, $kata));
    }

    private function format(Message $m, CounselingSession $session, int $viewerId): array
    {
        return [
            'id' => $m->id,
            'body' => $m->body,
            'created_at' => $m->created_at->toIso8601String(),
            'punya_saya' => $m->sender_id === $viewerId,
            'dari' => $m->sender_id === $session->user_id ? 'pengguna' : 'listener',
            // The listener and the handling psychologist see the flag; the user gets the crisis contacts instead.
            'flagged' => $viewerId !== $session->user_id && $m->flagged,
        ];
    }
}

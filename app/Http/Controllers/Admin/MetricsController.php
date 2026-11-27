<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CounselingSession;
use App\Models\Escalation;
use App\Models\Message;
use App\Models\Rating;

// Module 4: "measurable social outcomes" from the proposal. Counts only, never chat content.
class MetricsController extends Controller
{
    public function index()
    {
        // Completed = closed after the chat really started (an empty no-show does not count).
        $selesai = fn () => CounselingSession::whereIn('status', [CounselingSession::ENDED, CounselingSession::ESCALATED])
            ->whereNotNull('started_at');

        $penggunaDilayani = $selesai()->distinct()->count('user_id');
        $penggunaKembali = $selesai()->select('user_id')->groupBy('user_id')->havingRaw('count(*) > 1')->get()->count();

        $skor = Rating::selectRaw('score, count(*) as jumlah')->groupBy('score')->pluck('jumlah', 'score');

        return view('admin.metrics', [
            'sesiSelesai' => $selesai()->count(),
            'sesiDibooking' => CounselingSession::where('status', '!=', CounselingSession::CANCELLED)->count(),
            'penggunaDilayani' => $penggunaDilayani,
            'penggunaKembali' => $penggunaKembali,
            'eskalasi' => [
                'total' => Escalation::count(),
                'ditangani' => Escalation::whereNotNull('psychologist_id')->count(),
                'menunggu' => Escalation::where('status', Escalation::OPEN)->count(),
                'selesai' => Escalation::where('status', Escalation::RESOLVED)->count(),
            ],
            // Only the flag column is read; the encrypted bodies are never loaded.
            'pesanBertanda' => Message::where('flagged', true)->count(),
            'sesiBertanda' => Message::where('flagged', true)->distinct()->count('counseling_session_id'),
            'rataRating' => round((float) Rating::avg('score'), 1),
            'jumlahRating' => $skor->sum(),
            'sebaranSkor' => collect(range(5, 1))->mapWithKeys(fn ($s) => [$s => (int) ($skor[$s] ?? 0)]),
            'perTopik' => CounselingSession::where('status', '!=', CounselingSession::CANCELLED)
                ->selectRaw('topic, count(*) as jumlah')->groupBy('topic')->orderByDesc('jumlah')->pluck('jumlah', 'topic'),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

// Module 4: "measurable social outcomes" from the proposal. Counts only, never chat content.
class MetricsController extends Controller
{
    public function index()
    {
        return view('placeholder', [
            'modul' => 'Modul 4: Dashboard metrik',
            'judul' => 'Metrik (admin)',
            'todo' => [
                'Jumlah sesi selesai (status ended, started_at terisi).',
                'Repeat usage: jumlah user dengan lebih dari 1 sesi.',
                'Rujukan profesional: jumlah escalations, dan berapa yang sudah diambil psikolog.',
                'Alarm krisis: jumlah pesan dengan flagged = true (hitung saja, isinya tidak boleh dibaca).',
                'Rata-rata rating.',
            ],
        ]);
    }
}

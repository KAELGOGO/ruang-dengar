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
                'Jumlah sesi selesai (status ended/escalated).',
                'Repeat usage: jumlah user dengan lebih dari 1 sesi.',
                'Rujukan profesional: jumlah escalations (pisahkan source listener vs keyword).',
                'Rata-rata rating.',
            ],
        ]);
    }
}

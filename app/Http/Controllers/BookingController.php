<?php

namespace App\Http\Controllers;

use App\Models\CounselingSession;
use App\Services\ListenerMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

// Module 2: booking & matching (user side). Matching rules live in App\Services\ListenerMatcher.
class BookingController extends Controller
{
    public function create()
    {
        return view('booking.create', ['topics' => CounselingSession::TOPICS]);
    }

    public function store(Request $request, ListenerMatcher $matcher)
    {
        $data = $request->validate([
            'topic' => ['required', Rule::in(array_keys(CounselingSession::TOPICS))],
            // "Bicara sekarang" sends now=1 instead of a time (no-appointment entry point).
            'now' => ['nullable', 'boolean'],
            'scheduled_at' => ['required_without:now', 'nullable', 'date', 'after_or_equal:now'],
        ]);

        $at = $request->boolean('now') ? now() : Carbon::parse($data['scheduled_at']);
        $session = $matcher->book($request->user(), $data['topic'], $at);

        if (! $session) {
            return back()->withInput()->with('status', 'Belum ada listener yang tersedia di waktu itu. Coba jam lain.');
        }

        return redirect()->route('sessions.index')->with('status', 'Sesi berhasil dibuat bersama '.$session->listener->name.'.');
    }
}

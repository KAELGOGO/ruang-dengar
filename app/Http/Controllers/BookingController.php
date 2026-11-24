<?php

namespace App\Http\Controllers;

use App\Models\AvailabilitySlot;
use App\Models\CounselingSession;
use App\Models\User;
use App\Services\ListenerMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

// Module 2: booking & matching (user side). Matching rules live in App\Services\ListenerMatcher.
class BookingController extends Controller
{
    private const SUGGESTION_COUNT = 3;

    private const SUGGESTION_DAYS = 7;

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
        ], [
            'topic.required' => 'Pilih topik terlebih dahulu.',
            'topic.in' => 'Topik tidak valid.',
            'scheduled_at.required_without' => 'Pilih jadwal, atau tekan "Bicara sekarang".',
            'scheduled_at.date' => 'Format jadwal tidak valid.',
            'scheduled_at.after_or_equal' => 'Jadwal tidak boleh di masa lalu.',
        ]);

        $at = $request->boolean('now') ? now() : Carbon::parse($data['scheduled_at']);
        $session = $matcher->book($request->user(), $data['topic'], $at);

        if (! $session) {
            return back()->withInput()
                ->with('status', 'Belum ada listener yang tersedia di waktu itu. Coba jam lain.')
                ->with('suggestions', $this->nearestOpenTimes($request->user(), $matcher)->all());
        }

        return redirect()->route('sessions.index')->with('status', 'Sesi berhasil dibuat bersama '.$session->listener->name.'.');
    }

    /**
     * The next few slot start times (within a week) at which the matcher would find someone for this user.
     * Reuses ListenerMatcher::openSlots(), so the suggestions follow exactly the same matching rules.
     *
     * @return Collection<int, string> times as "Y-m-d H:i"
     */
    private function nearestOpenTimes(User $user, ListenerMatcher $matcher): Collection
    {
        $candidates = AvailabilitySlot::whereDoesntHave('counselingSession')
            ->whereBetween('starts_at', [now(), now()->addDays(self::SUGGESTION_DAYS)])
            ->orderBy('starts_at')
            ->pluck('starts_at')
            ->unique(fn ($time) => $time->format('Y-m-d H:i'));

        // lazy(): stop querying as soon as enough times are found.
        return $candidates->lazy()
            ->filter(fn ($time) => $matcher->openSlots($user, $time)->exists())
            ->take(self::SUGGESTION_COUNT)
            ->map(fn ($time) => $time->format('Y-m-d H:i'))
            ->values()
            ->collect();
    }
}

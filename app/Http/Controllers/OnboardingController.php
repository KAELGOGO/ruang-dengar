<?php

namespace App\Http\Controllers;

use App\Models\CounselingSession;
use App\Support\Mood;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// "Kenalan sama Dengi": a short, skippable welcome for new users right after registration.
class OnboardingController extends Controller
{
    public function show()
    {
        return view('onboarding', [
            'topics' => CounselingSession::TOPICS,
            'moods' => Mood::OPTIONS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nickname' => ['nullable', 'string', 'max:40'],
            'mood' => ['nullable', Rule::in(array_keys(Mood::OPTIONS))],
            'preferred_topic' => ['nullable', Rule::in(array_keys(CounselingSession::TOPICS))],
        ], [
            'nickname.max' => 'Nama panggilan maksimal 40 karakter.',
            'mood.in' => 'Pilihan perasaan tidak valid.',
            'preferred_topic.in' => 'Topik tidak valid.',
        ]);

        $user = $request->user();
        // Skipping still counts as done, so Dengi does not ask again.
        $user->forceFill($data + ['onboarded_at' => now()])->save();

        return redirect()->route('home')->with('status', 'Senang kenalan denganmu, '.$user->displayName().'! 🍎');
    }
}

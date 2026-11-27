<?php

namespace App\Http\Controllers;

use App\Models\CounselingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

// Module 4: rating after a session ends.
class RatingController extends Controller
{
    public function create(CounselingSession $session)
    {
        Gate::authorize('rate', $session);
        $session->load('listener.listenerProfile');

        return view('ratings.create', ['session' => $session]);
    }

    public function store(Request $request, CounselingSession $session)
    {
        // The policy also blocks a second rating (it requires $session->rating to be null).
        Gate::authorize('rate', $session);
        $data = $request->validate([
            'score' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $session->rating()->create($data);

        return redirect()->route('sessions.index')
            ->with('status', 'Terima kasih atas penilaianmu! Masukanmu membantu kami menjaga kualitas pendampingan.');
    }
}

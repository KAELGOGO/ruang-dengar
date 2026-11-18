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

        return view('placeholder', [
            'modul' => 'Modul 4: Rating',
            'judul' => 'Beri rating sesi #'.$session->id,
            'todo' => ['Form skor 1-5 + komentar opsional, kirim ke route ratings.store.'],
        ]);
    }

    public function store(Request $request, CounselingSession $session)
    {
        Gate::authorize('rate', $session);
        $request->validate([
            'score' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        // TODO (Module 4): $session->rating()->create($data), redirect to sessions.index.
        abort(501, 'Belum dibuat (Modul 4)');
    }
}

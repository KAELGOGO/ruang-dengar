<?php

namespace App\Http\Controllers\Listener;

use App\Http\Controllers\Controller;
use App\Models\ListenerProfile;
use Illuminate\Http\Request;

// Module 1: listener profile and credential upload.
class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $profile = $request->user()->listenerProfile()->firstOrCreate();
        $profile->load(['documents' => fn ($query) => $query->latest('id')]);

        // Shown only while rejected: the note from the most recently reviewed document.
        $rejectionNote = $profile->verification_status === ListenerProfile::REJECTED
            ? $profile->documents->whereNotNull('review_note')->first()?->review_note
            : null;

        return view('listener.profile', compact('profile', 'rejectionNote'));
    }

    public function update(Request $request)
    {
        $data = $request->validate(['bio' => ['nullable', 'string', 'max:1000']]);

        $request->user()->listenerProfile()->firstOrCreate()->update($data);

        return back()->with('status', 'Profil disimpan.');
    }

    public function upload(Request $request)
    {
        $request->validate(['document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120']]);

        $profile = $request->user()->listenerProfile()->firstOrCreate();
        $file = $request->file('document');

        // "local" disk = storage/app/private, not reachable from the browser.
        $path = $file->store('credentials');

        $profile->documents()->create([
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
        ]);

        // A rejected listener who uploads again goes back into the admin queue.
        if ($profile->verification_status === ListenerProfile::REJECTED) {
            $profile->forceFill(['verification_status' => ListenerProfile::PENDING])->save();
        }

        return back()->with('status', 'Dokumen berhasil diunggah dan menunggu pemeriksaan admin.');
    }
}

<?php

namespace App\Http\Controllers\Listener;

use App\Http\Controllers\Controller;
use App\Models\ListenerProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
        $data = $this->validateAt(
            $request,
            ['bio' => ['nullable', 'string', 'max:1000']],
            'kartu-bio',
            [
                'bio.string' => 'Bio harus berupa teks.',
                'bio.max' => 'Bio maksimal 1000 karakter.',
            ]
        );

        $request->user()->listenerProfile()->firstOrCreate()->update($data);

        return redirect($this->pageUrl('kartu-bio'))->with('bio_status', 'Profil disimpan.');
    }

    public function upload(Request $request)
    {
        $this->validateAt(
            $request,
            ['document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120']],
            'dokumen',
            [
                'document.required' => 'Pilih file terlebih dahulu.',
                'document.file' => 'Yang diunggah harus berupa file.',
                // PHP itself refuses files above upload_max_filesize before the max rule runs.
                'document.uploaded' => 'File gagal diunggah. Pastikan ukurannya tidak lebih dari 5 MB.',
                'document.mimes' => 'Format file harus PDF, JPG, atau PNG.',
                'document.max' => 'Ukuran file maksimal 5 MB.',
            ]
        );

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

        return redirect($this->pageUrl('dokumen'))
            ->with('doc_status', 'Dokumen berhasil diunggah dan menunggu pemeriksaan admin.');
    }

    /** Profile page URL with an #anchor, so the browser jumps to the card that was just used. */
    private function pageUrl(string $anchor): string
    {
        return route('listener.profile.edit').'#'.$anchor;
    }

    /** Same as $request->validate(), but errors send the user back to the card instead of the page top. */
    private function validateAt(Request $request, array $rules, string $anchor, array $messages = []): array
    {
        try {
            return $request->validate($rules, $messages);
        } catch (ValidationException $e) {
            throw $e->redirectTo($this->pageUrl($anchor));
        }
    }
}

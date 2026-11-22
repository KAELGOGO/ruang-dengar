<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ListenerDocument;
use App\Models\ListenerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

// Module 1: admin reviews listener documents and grants the listener title.
class VerificationController extends Controller
{
    public function index()
    {
        // Oldest first, so listeners who have waited longest are reviewed first.
        $profiles = ListenerProfile::with(['user', 'documents'])
            ->where('verification_status', ListenerProfile::PENDING)
            ->oldest()
            ->get();

        return view('admin.verifications.index', compact('profiles'));
    }

    /** Documents are on the private disk, so the admin downloads them through here. */
    public function document(ListenerDocument $document)
    {
        // The file may have been removed from disk while its row still exists.
        abort_unless(Storage::disk('local')->exists($document->path), 404, 'File dokumen tidak ditemukan.');

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    public function approve(Request $request, ListenerProfile $profile)
    {
        $data = $request->validate([
            'credential_level' => ['required', Rule::in([ListenerProfile::VOLUNTEER, ListenerProfile::PSYCH_STUDENT, ListenerProfile::PSYCHOLOGIST])],
        ]);

        // TODO (Module 1): forceFill credential_level, verification_status approved,
        // verified_by = auth id, verified_at = now(); mark the documents approved.
        abort(501, 'Belum dibuat (Modul 1)');
    }

    public function reject(Request $request, ListenerProfile $profile)
    {
        $request->validate(['note' => ['required', 'string', 'max:255']]);

        // TODO (Module 1): verification_status rejected; review_note on the documents.
        abort(501, 'Belum dibuat (Modul 1)');
    }
}

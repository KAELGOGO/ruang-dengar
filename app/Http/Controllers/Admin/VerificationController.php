<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ListenerDocument;
use App\Models\ListenerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        if ($blocked = $this->cannotReview($profile)) {
            return redirect()->route('admin.verifications.index')->with('status', $blocked);
        }

        DB::transaction(function () use ($request, $profile, $data) {
            $profile->forceFill([
                'credential_level' => $data['credential_level'],
                'verification_status' => ListenerProfile::APPROVED,
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ])->save();

            $this->markPendingDocuments($profile, 'approved', $request->user()->id);
        });

        return redirect()->route('admin.verifications.index')
            ->with('status', $profile->user->name.' disetujui sebagai '.ListenerProfile::TITLES[$data['credential_level']].'.');
    }

    public function reject(Request $request, ListenerProfile $profile)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:255']]);

        if ($blocked = $this->cannotReview($profile)) {
            return redirect()->route('admin.verifications.index')->with('status', $blocked);
        }

        DB::transaction(function () use ($request, $profile, $data) {
            $profile->forceFill(['verification_status' => ListenerProfile::REJECTED])->save();

            $this->markPendingDocuments($profile, 'rejected', $request->user()->id, $data['note']);
        });

        return redirect()->route('admin.verifications.index')
            ->with('status', 'Verifikasi '.$profile->user->name.' ditolak.');
    }

    /** Null when the profile can be reviewed, otherwise the reason (shown as a status message). */
    private function cannotReview(ListenerProfile $profile): ?string
    {
        if ($profile->verification_status !== ListenerProfile::PENDING) {
            return 'Profil ini sudah diproses sebelumnya.';
        }

        // The rejection note and the approval both live on the documents, so one must be waiting.
        if (! $profile->documents()->where('status', 'pending')->exists()) {
            return 'Tidak ada dokumen yang menunggu pemeriksaan untuk profil ini.';
        }

        return null;
    }

    private function markPendingDocuments(ListenerProfile $profile, string $status, int $adminId, ?string $note = null): void
    {
        $profile->documents()->where('status', 'pending')->update([
            'status' => $status,
            'review_note' => $note,
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
        ]);
    }
}

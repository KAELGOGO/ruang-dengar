<?php

namespace App\Http\Controllers\Listener;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Module 1: listener profile and credential upload.
class ProfileController extends Controller
{
    public function edit()
    {
        return view('placeholder', [
            'modul' => 'Modul 1: Verifikasi listener',
            'judul' => 'Profil listener',
            'todo' => [
                'Tampilkan status verifikasi (pending/approved/rejected) dan gelar listener.',
                'Form bio (route listener.profile.update).',
                'Form upload sertifikat/ijazah (route listener.documents.store), lihat method upload().',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['bio' => ['nullable', 'string', 'max:1000']]);

        // TODO (Module 1): $request->user()->listenerProfile->update($data)
        abort(501, 'Belum dibuat (Modul 1)');
    }

    public function upload(Request $request)
    {
        $request->validate(['document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120']]);

        // TODO (Module 1): $path = $request->file('document')->store('credentials');
        // ("local" disk = storage/app/private, not reachable from the browser), then
        // create a ListenerDocument with path + getClientOriginalName().
        abort(501, 'Belum dibuat (Modul 1)');
    }
}

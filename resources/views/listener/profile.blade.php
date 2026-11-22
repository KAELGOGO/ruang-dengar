@extends('layouts.app')

@php
    $statusBadge = [
        'pending' => ['warning', 'Menunggu verifikasi'],
        'approved' => ['success', 'Terverifikasi'],
        'rejected' => ['danger', 'Ditolak'],
    ];
    [$warna, $label] = $statusBadge[$profile->verification_status] ?? ['secondary', $profile->verification_status];
@endphp

@section('content')
<div class="container" style="max-width: 760px">
    <h1 class="h3 fw-bold">Profil listener</h1>

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="text-muted">Status verifikasi:</span>
                <span class="badge text-bg-{{ $warna }}">{{ $label }}</span>
            </div>

            @if ($profile->verification_status === 'approved' && $profile->credential_level)
                <p class="mb-0">
                    Gelar kamu:
                    <strong>{{ \App\Models\ListenerProfile::TITLES[$profile->credential_level] ?? $profile->credential_level }}</strong>
                </p>
            @elseif ($profile->verification_status === 'pending')
                <p class="mb-0 text-muted">
                    Admin akan memeriksa dokumenmu. Kamu baru bisa dipasangkan dengan pengguna setelah disetujui.
                </p>
            @elseif ($profile->verification_status === 'rejected')
                <div class="alert alert-danger mb-0">
                    <strong>Verifikasi ditolak.</strong>
                    @if ($rejectionNote)
                        Catatan admin: {{ $rejectionNote }}
                    @endif
                    <br>Silakan unggah dokumen yang benar untuk diperiksa ulang.
                </div>
            @endif
        </div>
    </div>

    <div class="card mb-4" id="kartu-bio">
        <div class="card-header fw-semibold">Bio</div>
        <div class="card-body">
            @if (session('bio_status'))
                <div class="alert alert-success">{{ session('bio_status') }}</div>
            @endif

            <form method="POST" action="{{ route('listener.profile.update') }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="bio" class="form-label">Ceritakan sedikit tentang dirimu</label>
                    <textarea id="bio" name="bio" rows="4" maxlength="1000"
                              class="form-control @error('bio') is-invalid @enderror">{{ old('bio', $profile->bio) }}</textarea>
                    @error('bio')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Simpan bio</button>
            </form>
        </div>
    </div>

    <div class="card mb-4" id="dokumen">
        <div class="card-header fw-semibold">Dokumen kredensial</div>
        <div class="card-body">
            @if (session('doc_status'))
                <div class="alert alert-success">{{ session('doc_status') }}</div>
            @endif
            @error('document')
                <div class="alert alert-danger">Dokumen tidak diunggah: {{ $message }}</div>
            @enderror

            @if ($profile->documents->isEmpty())
                <p class="text-muted">Belum ada dokumen. Unggah sertifikat atau ijazah supaya admin bisa memverifikasi kamu.</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Nama file</th>
                                <th>Status</th>
                                <th>Diunggah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($profile->documents as $dokumen)
                                @php [$warnaDok, $labelDok] = $statusBadge[$dokumen->status] ?? ['secondary', $dokumen->status]; @endphp
                                <tr>
                                    <td>{{ $dokumen->original_name }}</td>
                                    <td><span class="badge text-bg-{{ $warnaDok }}">{{ $labelDok }}</span></td>
                                    <td>{{ $dokumen->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <hr>

            <form method="POST" action="{{ route('listener.documents.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label for="document" class="form-label">Unggah sertifikat atau ijazah</label>
                    <input type="file" id="document" name="document" accept=".pdf,.jpg,.jpeg,.png"
                           class="form-control @error('document') is-invalid @enderror" required>
                    <div class="form-text">Format PDF, JPG, atau PNG. Maksimal 5 MB.</div>
                    @error('document')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Unggah dokumen</button>
            </form>
        </div>
    </div>
</div>
@endsection

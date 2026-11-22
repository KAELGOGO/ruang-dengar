@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 900px">
    <h1 class="h3 fw-bold">Verifikasi listener</h1>
    <p class="text-muted">Periksa dokumen kredensial, lalu setujui dengan memilih gelar atau tolak dengan catatan.</p>

    @forelse ($profiles as $profile)
        @php
            $user = $profile->user;
            $dokumenPending = $profile->documents->where('status', 'pending');
        @endphp

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">{{ $user->name }}</span>
                <span class="badge text-bg-warning">Menunggu verifikasi</span>
            </div>
            <div class="card-body">
                <dl class="row mb-3">
                    <dt class="col-sm-3">Email</dt>
                    <dd class="col-sm-9">{{ $user->email }}</dd>

                    <dt class="col-sm-3">Umur</dt>
                    <dd class="col-sm-9">
                        {{ \App\Support\AgeTier::age($user->birth_date) }} tahun
                        ({{ \App\Support\AgeTier::LABELS[$user->ageTier()] ?? '-' }})
                    </dd>

                    <dt class="col-sm-3">Bio</dt>
                    <dd class="col-sm-9">{{ $profile->bio ?: '-' }}</dd>

                    <dt class="col-sm-3">Dokumen</dt>
                    <dd class="col-sm-9">
                        @forelse ($profile->documents as $dokumen)
                            <div>
                                <a href="{{ route('admin.documents.show', $dokumen) }}">{{ $dokumen->original_name }}</a>
                                <span class="text-muted small">
                                    ({{ $dokumen->status }}, {{ $dokumen->created_at->format('d/m/Y H:i') }})
                                </span>
                            </div>
                        @empty
                            <span class="text-danger">Belum ada dokumen yang diunggah.</span>
                        @endforelse
                    </dd>
                </dl>

                <div class="row g-3">
                    <div class="col-md-6">
                        <form method="POST" action="{{ route('admin.verifications.approve', $profile) }}">
                            @csrf
                            <label for="level-{{ $profile->id }}" class="form-label">Setujui sebagai</label>
                            <select id="level-{{ $profile->id }}" name="credential_level" class="form-select mb-2" required>
                                @foreach (\App\Models\ListenerProfile::TITLES as $nilai => $judul)
                                    <option value="{{ $nilai }}">{{ $judul }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-success" @disabled($dokumenPending->isEmpty())>Setujui</button>
                            @if ($dokumenPending->isEmpty())
                                <div class="form-text">Tidak bisa disetujui atau ditolak tanpa dokumen yang menunggu pemeriksaan.</div>
                            @endif
                        </form>
                    </div>

                    <div class="col-md-6">
                        <form method="POST" action="{{ route('admin.verifications.reject', $profile) }}">
                            @csrf
                            <label for="note-{{ $profile->id }}" class="form-label">Tolak dengan catatan</label>
                            <input type="text" id="note-{{ $profile->id }}" name="note" maxlength="255"
                                   class="form-control mb-2" placeholder="Alasan penolakan" required>
                            <button type="submit" class="btn btn-outline-danger" @disabled($dokumenPending->isEmpty())>Tolak</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-secondary">Tidak ada listener yang menunggu verifikasi.</div>
    @endforelse
</div>
@endsection
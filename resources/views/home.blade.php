@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 760px">
    <h1 class="h3 fw-bold">Halo, {{ $user->name }}</h1>

    @if ($user->role === 'user')
        <p>Kelompok umurmu: <strong>{{ \App\Support\AgeTier::LABELS[$user->ageTier()] ?? '-' }}</strong></p>
        <a href="{{ route('booking.create') }}" class="btn btn-primary">Cari teman cerita</a>
        <a href="{{ route('sessions.index') }}" class="btn btn-outline-secondary">Sesi saya</a>

    @elseif ($user->role === 'listener')
        @php($profil = $user->listenerProfile)
        <p>
            Status verifikasi: <strong>{{ $profil?->verification_status ?? 'pending' }}</strong>
            @if ($profil?->credential_level)
                · Gelar: <strong>{{ \App\Models\ListenerProfile::TITLES[$profil->credential_level] }}</strong>
            @endif
        </p>
        @unless ($user->isVerifiedListener())
            <div class="alert alert-info">Upload dokumen di Profil listener. Kamu baru bisa dipasangkan dengan pengguna setelah admin memverifikasi.</div>
        @endunless
        <a href="{{ route('listener.slots.index') }}" class="btn btn-primary">Atur jadwal</a>
        <a href="{{ route('sessions.index') }}" class="btn btn-outline-secondary">Sesi saya</a>
        @can('handle-escalations')
            <a href="{{ route('escalations.index') }}" class="btn btn-outline-danger">Antrean eskalasi</a>
        @endcan

    @else
        <a href="{{ route('admin.verifications.index') }}" class="btn btn-primary">Verifikasi listener</a>
        <a href="{{ route('admin.metrics') }}" class="btn btn-outline-secondary">Metrik</a>
    @endif
</div>
@endsection

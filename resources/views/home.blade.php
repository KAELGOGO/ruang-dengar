@extends('layouts.app')

@use('App\Models\ListenerProfile')
@use('App\Models\User')
@use('App\Support\AgeTier')

@php
    $verifikasi = [
        ListenerProfile::PENDING => ['warning', 'Menunggu verifikasi'],
        ListenerProfile::APPROVED => ['success', 'Terverifikasi'],
        ListenerProfile::REJECTED => ['danger', 'Ditolak'],
    ];
    $namaDepan = $user->displayName();
@endphp

@section('content')
<div class="container" style="max-width: 820px">

    @if ($user->role === User::ROLE_USER)
        <x-dengi size="110" class="mb-4">
            Hai {{ $namaDepan }}! {{ ($user->onboarded_at?->isToday() ? \App\Support\Mood::reply($user->mood) : null) ?? 'Apa kabar hari ini? 🌷' }}
        </x-dengi>

        @if ($user->needsOnboarding())
            <a href="{{ route('onboarding.show') }}" class="chip text-decoration-none mb-3">🍎 Kenalan dulu sama Dengi →</a>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-md-7 pop-in">
                <a href="{{ route('booking.create') }}" class="card card-action h-100 text-decoration-none bg-sage-soft">
                    <div class="card-body p-4">
                        <span class="icon-bubble bg-peach-soft mb-3" aria-hidden="true">💬</span>
                        <h1 class="h4 mb-1 text-body">Mau cerita sekarang?</h1>
                        <p class="text-body-secondary mb-0">Dengi carikan teman cerita yang pas.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-5 pop-in">
                {{-- A tiny breathing exercise: pure CSS, nothing is stored. --}}
                <div class="card h-100 bg-lavender-soft">
                    <div class="card-body p-4 d-flex flex-column align-items-center justify-content-center text-center gap-2">
                        <div class="breath" aria-hidden="true"></div>
                        <div class="h6 mb-0"><span class="breath-label"></span></div>
                        <small class="text-body-secondary">Pelan-pelan saja.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('sessions.index') }}" class="chip text-decoration-none">🗓️ Sesi saya</a>
            <span class="chip">🌿 {{ AgeTier::LABELS[$user->ageTier()] ?? '-' }}</span>
            <a href="{{ route('krisis') }}" class="chip text-decoration-none text-danger">🆘 Bantuan darurat</a>
        </div>

    @elseif ($user->role === User::ROLE_LISTENER)
        @php($profil = $user->listenerProfile)
        @php([$warna, $label] = $verifikasi[$profil?->verification_status ?? ListenerProfile::PENDING])

        <x-dengi size="110" class="mb-4">
            @if ($user->isVerifiedListener())
                Makasih sudah jadi pendengar, {{ $namaDepan }}! 💚
            @else
                Hai {{ $namaDepan }}! Upload dokumenmu dulu ya, nanti admin cek.
            @endif
        </x-dengi>

        <div class="d-flex flex-wrap gap-2 mb-4">
            <span class="badge text-bg-{{ $warna }} fs-6">{{ $label }}</span>
            @if ($user->isVerifiedListener())
                <span class="chip">🏅 {{ ListenerProfile::TITLES[$profil->credential_level] }}</span>
            @endif
        </div>

        @php($menu = array_filter([
            [route('listener.slots.index'), '🗓️', 'bg-sage-soft', 'Atur jadwal', 'Kapan kamu siap mendengarkan.'],
            [route('sessions.index'), '💬', 'bg-lavender-soft', 'Sesi saya', 'Teman cerita yang sudah booking.'],
            [route('listener.profile.edit'), '📄', 'bg-peach-soft', 'Profil listener', 'Bio dan dokumen.'],
            $user->can('handle-escalations')
                ? [route('escalations.index'), '🩺', 'bg-peach-soft', 'Antrean eskalasi', 'Kasus yang dioper ke psikolog.']
                : null,
        ]))

    @else
        <x-dengi size="110" class="mb-4">Halo admin! Siap merapikan hari ini? ✨</x-dengi>

        @php($menu = [
            [route('admin.verifications.index'), '🛡️', 'bg-sage-soft', 'Verifikasi listener', 'Periksa dokumen, beri gelar.'],
            [route('admin.metrics'), '📊', 'bg-lavender-soft', 'Metrik', 'Dampak layanan dalam angka.'],
        ])
    @endif

    @isset($menu)
        <div class="row g-3">
            @foreach ($menu as [$url, $ikon, $latar, $judul, $isi])
                <div class="col-sm-6 pop-in">
                    <a href="{{ $url }}" class="card card-action h-100 text-decoration-none">
                        <div class="card-body d-flex align-items-center gap-3 p-4">
                            <span class="icon-bubble {{ $latar }}" aria-hidden="true">{{ $ikon }}</span>
                            <div>
                                <h2 class="h5 mb-0 text-body">{{ $judul }}</h2>
                                <small class="text-body-secondary">{{ $isi }}</small>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endisset
</div>
@endsection

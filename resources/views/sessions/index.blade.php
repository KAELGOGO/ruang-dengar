@extends('layouts.app')

@php
    $statusBadge = [
        'scheduled' => ['primary', 'Terjadwal'],
        'active' => ['success', 'Berlangsung'],
        'ended' => ['secondary', 'Selesai'],
        'escalated' => ['warning', 'Dioper ke psikolog'],
        'cancelled' => ['light border', 'Dibatalkan'],
    ];
    $sections = [
        ['judul' => 'Akan datang', 'sesi' => $upcoming, 'kosong' => 'Tidak ada sesi yang akan datang.'],
        ['judul' => 'Riwayat', 'sesi' => $history, 'kosong' => 'Belum ada riwayat sesi.'],
    ];
@endphp

@section('content')
<div class="container" style="max-width: 860px">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <h1 class="h3 fw-bold mb-0 me-auto">Sesi saya</h1>
        @if (auth()->user()->role === \App\Models\User::ROLE_USER)
            <a href="{{ route('booking.create') }}" class="btn btn-primary">Cari teman cerita</a>
        @endif
    </div>

    @foreach ($sections as $section)
        <h2 class="h5 fw-bold mt-4">{{ $section['judul'] }} <span class="text-muted fw-normal">({{ $section['sesi']->count() }})</span></h2>

        <div class="list-group">
            @forelse ($section['sesi'] as $sesi)
                @php
                    $sayaPengguna = $sesi->user_id === auth()->id();
                    $lawan = $sayaPengguna ? $sesi->listener : $sesi->user;
                    $gelar = \App\Models\ListenerProfile::TITLES[$sesi->listener->listenerProfile?->credential_level] ?? null;
                    [$warna, $label] = $statusBadge[$sesi->status] ?? ['secondary', $sesi->status];
                @endphp
                <div class="list-group-item py-3">
                    <div class="d-flex flex-wrap align-items-start gap-2">
                        <div class="me-auto">
                            <div class="fw-semibold">
                                {{ $sesi->scheduled_at->locale('id')->isoFormat('dddd, D MMMM YYYY · HH:mm') }}
                            </div>
                            <div>
                                {{ $sayaPengguna ? 'Listener' : 'Pengguna' }}: <strong>{{ $lawan->name }}</strong>
                                @if ($sayaPengguna && $gelar)
                                    <span class="badge rounded-pill text-bg-light border">{{ $gelar }}</span>
                                @endif
                            </div>
                            <div class="text-muted small">Topik: {{ \App\Models\CounselingSession::TOPICS[$sesi->topic] ?? $sesi->topic }}</div>
                        </div>
                        <div class="d-flex flex-column align-items-end gap-1">
                            <span class="badge text-bg-{{ $warna }}">{{ $label }}</span>
                            @if ($sesi->escalation && $sesi->status !== 'escalated')
                                <span class="badge text-bg-warning">Dioper ke psikolog</span>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @can('view', $sesi)
                            <a href="{{ route('chat.show', $sesi) }}" class="btn btn-sm btn-primary">Chat</a>
                        @endcan
                        @can('cancel', $sesi)
                            <form method="POST" action="{{ route('sessions.cancel', $sesi) }}"
                                  onsubmit="return confirm('Batalkan sesi ini?')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger">Batalkan</button>
                            </form>
                        @endcan
                        {{-- Module 4 puts its rating/escalation buttons in this partial, so nobody edits this file at the same time. --}}
                        @include('sessions._aksi-modul-4', ['sesi' => $sesi])
                    </div>
                </div>
            @empty
                <div class="list-group-item text-muted">{{ $section['kosong'] }}</div>
            @endforelse
        </div>
    @endforeach
</div>
@endsection

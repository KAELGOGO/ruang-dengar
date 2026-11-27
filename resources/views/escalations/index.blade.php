@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 900px">
    <h1 class="h3 fw-bold">Antrean eskalasi</h1>
    <p class="text-muted">
        Kasus yang dioper listener ke psikolog. Yang paling lama menunggu ada di atas. Selama menunggu,
        pengguna masih ditemani listener-nya. "Ambil kasus" langsung membuat sesi baru antara kamu dan pengguna.
    </p>

    @forelse ($escalations as $kasus)
        @php
            $sesiLama = $kasus->counselingSession;
            $pengguna = $sesiLama->user;
            $listener = $sesiLama->listener;
            $gelar = \App\Models\ListenerProfile::TITLES[$listener->listenerProfile?->credential_level] ?? null;
            $milikSaya = $kasus->psychologist_id === auth()->id();
            $sesiSendiri = $sesiLama->listener_id === auth()->id();
            $sesiBaru = $milikSaya ? $kasus->followUpSession() : null;
        @endphp

        <div @class(['card mb-3', 'border-warning' => $kasus->status === 'open'])>
            <div class="card-header d-flex flex-wrap align-items-center gap-2">
                <span class="fw-semibold me-auto">{{ $pengguna->name }}</span>
                <span class="badge text-bg-light border">Dari: {{ \App\Models\Escalation::SOURCES[$kasus->source] ?? $kasus->source }}</span>
                @if ($kasus->status === 'open')
                    <span class="badge text-bg-warning">Menunggu psikolog</span>
                @else
                    <span class="badge text-bg-success">Kamu tangani</span>
                @endif
            </div>
            <div class="card-body">
                <dl class="row mb-2">
                    <dt class="col-sm-3">Kelompok umur</dt>
                    <dd class="col-sm-9">{{ \App\Support\AgeTier::LABELS[$pengguna->ageTier()] ?? '-' }}</dd>

                    <dt class="col-sm-3">Listener</dt>
                    <dd class="col-sm-9">
                        {{ $listener->name }}
                        @if ($gelar)<span class="badge rounded-pill text-bg-light border">{{ $gelar }}</span>@endif
                    </dd>

                    <dt class="col-sm-3">Topik</dt>
                    <dd class="col-sm-9">{{ \App\Models\CounselingSession::TOPICS[$sesiLama->topic] ?? $sesiLama->topic }}</dd>

                    <dt class="col-sm-3">Dioper</dt>
                    <dd class="col-sm-9">
                        {{ $kasus->created_at->locale('id')->isoFormat('D MMM YYYY, HH:mm') }}
                        <span class="text-muted">({{ $kasus->created_at->locale('id')->diffForHumans() }})</span>
                    </dd>

                    <dt class="col-sm-3">Alasan</dt>
                    <dd class="col-sm-9" style="white-space: pre-line">{{ $kasus->reason }}</dd>
                </dl>

                <div class="d-flex flex-wrap gap-2">
                    @if ($kasus->status === 'open')
                        @if ($sesiSendiri)
                            <span class="text-muted small">Kasus ini dari sesimu sendiri, jadi harus diambil psikolog lain.</span>
                        @else
                            <form method="POST" action="{{ route('escalations.take', $kasus) }}"
                                  onsubmit="return confirm('Ambil kasus ini? Sesi baru dengan pengguna akan dibuat mulai sekarang.')">
                                @csrf
                                <button type="submit" class="btn btn-primary">Ambil kasus</button>
                            </form>
                        @endif
                    @elseif ($milikSaya)
                        @if ($sesiBaru)
                            <a href="{{ route('chat.show', $sesiBaru) }}" class="btn btn-primary">Chat dengan pengguna</a>
                        @endif
                        {{-- Allowed by CounselingSessionPolicy@view once the case is ours (read only). --}}
                        <a href="{{ route('chat.show', $sesiLama) }}" class="btn btn-outline-secondary">Baca chat sebelumnya</a>
                        <form method="POST" action="{{ route('escalations.resolve', $kasus) }}"
                              onsubmit="return confirm('Tandai kasus ini selesai? Kasus akan hilang dari antrean.')">
                            @csrf
                            <button type="submit" class="btn btn-outline-success">Selesai</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-secondary">Tidak ada kasus yang menunggu. Terima kasih sudah berjaga.</div>
    @endforelse
</div>
@endsection

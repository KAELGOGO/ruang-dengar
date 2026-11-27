@extends('layouts.app')

@php
    $pilihan = [
        1 => 'Tidak membantu',
        2 => 'Kurang membantu',
        3 => 'Cukup membantu',
        4 => 'Membantu',
        5 => 'Sangat membantu',
    ];
    $gelar = \App\Models\ListenerProfile::TITLES[$session->listener->listenerProfile?->credential_level] ?? null;
@endphp

@section('content')
<div class="container" style="max-width: 640px">
    <h1 class="h3 fw-bold">Bagaimana sesimu?</h1>
    <p class="text-muted">
        Sesi dengan <strong>{{ $session->listener->name }}</strong>
        @if ($gelar)<span class="badge rounded-pill text-bg-light border">{{ $gelar }}</span>@endif
        · {{ $session->scheduled_at->locale('id')->isoFormat('dddd, D MMMM YYYY · HH:mm') }}
        · {{ \App\Models\CounselingSession::TOPICS[$session->topic] ?? $session->topic }}
    </p>

    <form method="POST" action="{{ route('ratings.store', $session) }}" class="card card-body">
        @csrf

        <fieldset class="mb-3">
            <legend class="form-label fw-semibold fs-6">Seberapa membantu sesi ini?</legend>
            <div class="d-flex flex-wrap gap-2">
                @foreach ($pilihan as $skor => $label)
                    {{-- btn-check: real radio buttons, styled as Bootstrap buttons. --}}
                    <input type="radio" class="btn-check" name="score" id="score-{{ $skor }}" value="{{ $skor }}"
                           autocomplete="off" required @checked((int) old('score') === $skor)>
                    <label class="btn btn-outline-warning text-dark" for="score-{{ $skor }}">
                        <span aria-hidden="true">{{ str_repeat('★', $skor) }}</span>
                        <span class="d-block small">{{ $skor }} · {{ $label }}</span>
                    </label>
                @endforeach
            </div>
            @error('score') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </fieldset>

        <div class="mb-3">
            <label for="comment" class="form-label fw-semibold">Komentar <span class="text-muted fw-normal">(opsional)</span></label>
            <textarea id="comment" name="comment" rows="3" maxlength="1000"
                      class="form-control @error('comment') is-invalid @enderror"
                      placeholder="Apa yang membantu, atau apa yang bisa lebih baik?">{{ old('comment') }}</textarea>
            @error('comment') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">Penilaian hanya bisa dikirim sekali dan dipakai untuk metrik layanan, tanpa menampilkan isi chat.</div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Kirim penilaian</button>
            <a href="{{ route('sessions.index') }}" class="btn btn-link">Nanti saja</a>
        </div>
    </form>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 980px">
    <section class="row align-items-center g-4 py-md-4 mb-5">
        <div class="col-md-6 order-md-2 text-center">
            <x-dengi size="230" class="flex-column">
                Hai, aku Dengi! 🍎<br>Sini, cerita sama kami.
            </x-dengi>
        </div>
        <div class="col-md-6 pop-in">
            <h1 class="hero-title fw-semibold mb-3">Ceritakan saja,<br>kami dengarkan.</h1>
            <p class="lead text-body-secondary mb-4">Teman cerita terverifikasi, tanpa janji temu, tanpa penghakiman.</p>
            <div class="d-flex flex-wrap gap-2">
                @auth
                    <a href="{{ route('home') }}" class="btn btn-primary btn-lg">Ke beranda</a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Mulai cerita</a>
                    <a href="{{ route('login') }}" class="btn btn-light btn-lg">Masuk</a>
                @endauth
            </div>
        </div>
    </section>

    <section class="mb-5">
        <h2 class="h3 text-center mb-4">Cara kerjanya</h2>
        <div class="row g-3">
            @foreach ([
                ['💬', 'bg-sage-soft', 'Pilih topik', 'Apa saja yang sedang kamu rasakan.'],
                ['🍎', 'bg-peach-soft', 'Dengi carikan teman', 'Listener seumuran didahulukan.'],
                ['🌷', 'bg-lavender-soft', 'Cerita, lalu lega', 'Chat terenkripsi, hanya kalian berdua.'],
            ] as $i => [$ikon, $latar, $judul, $isi])
                <div class="col-md-4 pop-in">
                    <div class="card h-100 text-center">
                        <div class="card-body p-4">
                            <span class="icon-bubble {{ $latar }} mb-3" aria-hidden="true">{{ $ikon }}</span>
                            <h3 class="h5 mb-1">{{ $i + 1 }}. {{ $judul }}</h3>
                            <p class="text-body-secondary mb-0">{{ $isi }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="d-flex flex-wrap justify-content-center gap-2 mb-5">
        <span class="chip">🛡️ Listener terverifikasi</span>
        <span class="chip">🌿 Aman sesuai umur</span>
        <span class="chip">🩺 Bisa dioper ke psikolog</span>
        @guest
            <a href="{{ route('register') }}" class="chip text-decoration-none">🫶 Mau jadi listener?</a>
        @endguest
    </section>

    <p class="text-center small text-body-secondary">
        Bukan layanan darurat. Dalam bahaya sekarang? Buka <a href="{{ route('krisis') }}" class="text-danger fw-bold">bantuan darurat</a>.
    </p>
</div>
@endsection

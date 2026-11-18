@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 760px">
    <h1 class="display-5 fw-bold">{{ config('app.name') }}</h1>
    <p class="lead">Ruang aman untuk bercerita. Kamu dipasangkan dengan listener terverifikasi yang sesuai kelompok umurmu.</p>

    <div class="d-flex gap-2 my-4">
        @auth
            <a href="{{ route('home') }}" class="btn btn-primary btn-lg">Ke beranda</a>
        @else
            <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Daftar</a>
            <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-lg">Masuk</a>
        @endauth
    </div>

    <div class="alert alert-warning">
        <strong>Bukan layanan darurat.</strong> Kalau kamu atau orang lain dalam bahaya sekarang,
        buka <a href="{{ route('krisis') }}">kontak bantuan darurat</a>.
    </div>
</div>
@endsection

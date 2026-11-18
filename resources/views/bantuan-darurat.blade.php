@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 760px">
    <h1 class="h2 fw-bold">Bantuan darurat</h1>
    <p>Kalau kamu sedang berpikir untuk menyakiti diri sendiri atau dalam bahaya, hubungi layanan ini sekarang.</p>

    @include('partials.kontak-krisis')
</div>
@endsection

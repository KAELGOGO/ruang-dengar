{{-- Temporary page for routes whose module is not built yet. Replace with a real view. --}}
@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 760px">
    <span class="badge text-bg-secondary">{{ $modul }}</span>
    <h1 class="h3 fw-bold mt-2">{{ $judul }}</h1>
    <p class="text-muted">Halaman ini belum dibuat. Yang perlu dikerjakan:</p>
    <ul>
        @foreach ($todo as $item)
            <li>{{ $item }}</li>
        @endforeach
    </ul>
</div>
@endsection

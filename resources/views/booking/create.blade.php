@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 640px">
    <h1 class="h3 fw-bold">Cari teman cerita</h1>
    <p class="text-muted">Pilih topik, lalu bicara sekarang atau pilih jadwal. Sistem memilihkan listener terverifikasi yang sesuai kelompok umurmu.</p>

    <form method="POST" action="{{ route('booking.store') }}" class="card card-body">
        @csrf
        <label for="topic" class="form-label">Topik</label>
        <select id="topic" name="topic" class="form-select @error('topic') is-invalid @enderror" required>
            @foreach ($topics as $kode => $label)
                <option value="{{ $kode }}" @selected(old('topic') === $kode)>{{ $label }}</option>
            @endforeach
        </select>
        @error('topic') <div class="invalid-feedback">{{ $message }}</div> @enderror

        <button type="submit" name="now" value="1" class="btn btn-primary btn-lg mt-3">Bicara sekarang</button>

        <hr>
        <label for="scheduled_at" class="form-label">Atau pilih jadwal</label>
        <input id="scheduled_at" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}"
               class="form-control @error('scheduled_at') is-invalid @enderror">
        @error('scheduled_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <button type="submit" class="btn btn-outline-primary mt-2">Buat jadwal</button>
    </form>
</div>
@endsection

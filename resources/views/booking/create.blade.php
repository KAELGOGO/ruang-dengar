@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 640px">
    <x-dengi size="100" class="mb-4">Mau cerita tentang apa? Aku carikan teman yang pas 🍎</x-dengi>

    @if (session('suggestions'))
        <div class="card bg-peach-soft mb-3 pop-in">
            <div class="card-body">
                <h2 class="h6 mb-2">Yang ini ada yang kosong:</h2>
                <div class="d-flex flex-wrap gap-2">
                    @foreach (session('suggestions') as $waktu)
                        <form method="POST" action="{{ route('booking.store') }}">
                            @csrf
                            <input type="hidden" name="topic" value="{{ old('topic') }}">
                            <input type="hidden" name="scheduled_at" value="{{ $waktu }}">
                            <button type="submit" class="btn btn-light btn-sm">
                                {{ \Illuminate\Support\Carbon::parse($waktu)->locale('id')->isoFormat('ddd, D MMM · HH:mm') }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('booking.store') }}" class="card card-body p-4 pop-in">
        @csrf
        <fieldset>
            <legend class="h6 mb-2">Topik</legend>
            <div class="d-flex flex-wrap gap-2">
                @foreach ($topics as $kode => $label)
                    <input type="radio" class="btn-check" name="topic" id="topic-{{ $kode }}" value="{{ $kode }}"
                           @checked(old('topic', auth()->user()->preferred_topic ?? array_key_first($topics)) === $kode) required>
                    <label class="btn btn-choice" for="topic-{{ $kode }}">{{ $label }}</label>
                @endforeach
            </div>
            @error('topic') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </fieldset>

        <button type="submit" name="now" value="1" class="btn btn-primary btn-lg mt-4">Bicara sekarang</button>

        <details class="mt-3" @if ($errors->has('scheduled_at') || old('scheduled_at')) open @endif>
            <summary class="text-body-secondary fw-semibold">Atau pilih jadwal nanti</summary>
            <div class="mt-2">
                <label for="scheduled_at" class="visually-hidden">Jadwal</label>
                <input id="scheduled_at" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}"
                       min="{{ now()->format('Y-m-d\TH:i') }}"
                       class="form-control @error('scheduled_at') is-invalid @enderror">
                @error('scheduled_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <button type="submit" class="btn btn-outline-primary w-100 mt-2">Buat jadwal</button>
            </div>
        </details>
    </form>
</div>
@endsection

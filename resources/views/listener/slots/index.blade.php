@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 860px">
    <h1 class="h3 fw-bold">Jadwal saya</h1>
    <p class="text-muted">Atur kapan kamu siap mendengarkan. Pengguna hanya bisa dipasangkan denganmu di dalam slot ini.</p>

    @unless ($isVerified)
        <div class="alert alert-warning">
            Slot baru dipakai setelah akunmu diverifikasi admin.
            Upload dokumenmu di <a href="{{ route('listener.profile.edit') }}" class="alert-link">Profil listener</a>.
        </div>
    @endunless

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header fw-semibold">Tambah slot</div>
                <form method="POST" action="{{ route('listener.slots.store') }}" class="card-body">
                    @csrf
                    <div class="mb-3">
                        <label for="date" class="form-label">Tanggal</label>
                        <input id="date" type="date" name="date" min="{{ today()->toDateString() }}"
                               value="{{ old('date', today()->toDateString()) }}"
                               class="form-control @error('date') is-invalid @enderror" required>
                        @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="start_time" class="form-label">Jam mulai</label>
                            <input id="start_time" type="time" name="start_time" value="{{ old('start_time') }}"
                                   class="form-control @error('start_time') is-invalid @enderror" required>
                            @error('start_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label for="end_time" class="form-label">Jam selesai</label>
                            <input id="end_time" type="time" name="end_time" value="{{ old('end_time') }}"
                                   class="form-control @error('end_time') is-invalid @enderror" required>
                            @error('end_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <p class="form-text mt-0">
                        Durasi {{ \App\Models\AvailabilitySlot::MIN_MINUTES }}–{{ \App\Models\AvailabilitySlot::MAX_MINUTES }} menit,
                        tidak boleh bertumpuk dengan slotmu yang lain.
                    </p>
                    <button type="submit" class="btn btn-primary w-100">Simpan slot</button>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <h2 class="h5 fw-bold">Slot mendatang</h2>
            <div class="list-group">
                @forelse ($slots as $slot)
                    @php($sesi = $slot->counselingSession)
                    <div class="list-group-item d-flex flex-wrap align-items-center gap-2">
                        <div class="me-auto">
                            <div class="fw-semibold">{{ $slot->starts_at->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</div>
                            <div class="text-muted small">
                                {{ $slot->starts_at->format('H:i') }}–{{ $slot->ends_at->format('H:i') }}
                                @if ($slot->starts_at->isPast())
                                    <span class="badge text-bg-info ms-1">Sedang berjalan</span>
                                @endif
                            </div>
                        </div>

                        @if ($sesi)
                            <div class="text-sm-end small">
                                <span class="badge text-bg-primary">Dibooking</span>
                                <div>{{ $sesi->user->name }} · {{ \App\Models\CounselingSession::TOPICS[$sesi->topic] ?? $sesi->topic }}</div>
                            </div>
                        @else
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge text-bg-light border">Kosong</span>
                                <form method="POST" action="{{ route('listener.slots.destroy', $slot) }}"
                                      onsubmit="return confirm('Hapus slot ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="list-group-item text-muted">Belum ada slot. Tambahkan slot pertamamu di samping.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

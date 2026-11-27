@extends('layouts.app')

@php
    // Percentage for progress bars, safe when the total is 0.
    $persen = fn ($bagian, $total) => $total > 0 ? round($bagian / $total * 100) : 0;
    $kartu = [
        ['Sesi selesai', $sesiSelesai, 'dari '.$sesiDibooking.' sesi yang dibooking'],
        ['Pengguna kembali lagi', $penggunaKembali, $persen($penggunaKembali, $penggunaDilayani).'% dari '.$penggunaDilayani.' pengguna yang terlayani'],
        ['Rujukan ke psikolog', $eskalasi['total'], $eskalasi['ditangani'].' sudah ditangani psikolog'],
        ['Rata-rata rating', $jumlahRating ? number_format($rataRating, 1, ',', '.').' / 5' : '-', 'dari '.$jumlahRating.' penilaian'],
    ];
@endphp

@section('content')
<div class="container" style="max-width: 960px">
    <h1 class="h3 fw-bold">Metrik dampak</h1>
    <p class="text-muted">
        Angka ringkasan layanan untuk mengukur dampak sosial. Halaman ini hanya berisi hitungan:
        isi chat tidak pernah ditampilkan ke admin.
    </p>

    <div class="row g-3 mb-4">
        @foreach ($kartu as [$judul, $angka, $keterangan])
            <div class="col-sm-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">{{ $judul }}</div>
                        <div class="display-6 fw-bold">{{ $angka }}</div>
                        <div class="small text-muted">{{ $keterangan }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header fw-semibold">Rujukan ke psikolog</div>
                <div class="card-body">
                    @foreach ([['Ditangani psikolog', $eskalasi['ditangani'], 'success'], ['Masih menunggu', $eskalasi['menunggu'], 'warning'], ['Sudah selesai', $eskalasi['selesai'], 'info']] as [$label, $nilai, $warna])
                        <div class="d-flex justify-content-between small"><span>{{ $label }}</span><span>{{ $nilai }} / {{ $eskalasi['total'] }}</span></div>
                        <div class="progress mb-3" role="progressbar" aria-label="{{ $label }}"
                             aria-valuenow="{{ $persen($nilai, $eskalasi['total']) }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-{{ $warna }}" style="width: {{ $persen($nilai, $eskalasi['total']) }}%"></div>
                        </div>
                    @endforeach
                    <p class="small text-muted mb-0">
                        Rasio rujukan: {{ $persen($eskalasi['total'], $sesiSelesai) }}% dari sesi selesai.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header fw-semibold">Sebaran rating</div>
                <div class="card-body">
                    @foreach ($sebaranSkor as $skor => $jumlah)
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="small text-nowrap" style="width: 4.5rem"><span aria-hidden="true">{{ str_repeat('★', $skor) }}</span><span class="visually-hidden">{{ $skor }} bintang</span></span>
                            <div class="progress flex-grow-1" role="progressbar" aria-label="{{ $skor }} bintang"
                                 aria-valuenow="{{ $persen($jumlah, $jumlahRating) }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar bg-warning" style="width: {{ $persen($jumlah, $jumlahRating) }}%"></div>
                            </div>
                            <span class="small text-end" style="width: 2rem">{{ $jumlah }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header fw-semibold">Sesi per topik</div>
                <div class="card-body">
                    @forelse ($perTopik as $topik => $jumlah)
                        <div class="d-flex justify-content-between small">
                            <span>{{ \App\Models\CounselingSession::TOPICS[$topik] ?? $topik }}</span><span>{{ $jumlah }}</span>
                        </div>
                        <div class="progress mb-3" role="progressbar" aria-label="{{ \App\Models\CounselingSession::TOPICS[$topik] ?? $topik }}"
                             aria-valuenow="{{ $persen($jumlah, $sesiDibooking) }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: {{ $persen($jumlah, $sesiDibooking) }}%"></div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Belum ada sesi.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100 border-danger-subtle">
                <div class="card-header fw-semibold">Alarm kata kunci krisis</div>
                <div class="card-body">
                    <div class="display-6 fw-bold">{{ $pesanBertanda }}</div>
                    <p class="small text-muted mb-0">
                        pesan bertanda kata kunci, di {{ $sesiBertanda }} sesi. Hanya jumlahnya yang dihitung;
                        isi pesan tetap terenkripsi dan tidak dibuka.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

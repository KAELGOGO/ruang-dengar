@extends('layouts.app')

@section('content')
@php
    $saya = auth()->user();
    $peserta = $session->hasParticipant($saya);
    $gelar = \App\Models\ListenerProfile::TITLES[$session->listener->listenerProfile?->credential_level] ?? null;
@endphp
<div class="container" style="max-width: 960px">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-1">
                @if (! $peserta)
                    Chat {{ $session->user->name }} &amp; {{ $session->listener->name }}
                @elseif ($saya->id === $session->user_id)
                    Chat dengan {{ $session->listener->name }}
                    @if ($gelar)<span class="badge text-bg-success align-middle fs-6 fw-normal">{{ $gelar }}</span>@endif
                @else
                    Chat dengan {{ $session->user->name }}
                @endif
            </h1>
            <div class="text-muted small">
                {{ \App\Models\CounselingSession::TOPICS[$session->topic] ?? $session->topic }}
                · {{ $session->scheduled_at->format('d M Y, H:i') }}@if ($session->slot)–{{ $session->slot->ends_at->format('H:i') }}@endif
            </div>
        </div>
        <div class="d-flex gap-2 align-items-start">
            @include('chat._eskalasi', ['sesi' => $session])
            @if ($bisaAkhiri)
                <form method="POST" action="{{ route('chat.end', $session) }}"
                      onsubmit="return confirm('Akhiri sesi ini? Chat tidak bisa dilanjutkan lagi.')">
                    @csrf
                    <button class="btn btn-outline-secondary">Akhiri sesi</button>
                </form>
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div id="daftar-pesan" class="border rounded p-3 bg-light" style="height: 55vh; overflow-y: auto"
                 role="log" aria-live="polite" aria-label="Pesan">
                <p id="kosong" class="text-muted small text-center my-5">Belum ada pesan.</p>
            </div>

            @if ($keterangan === null)
                {{-- method="post": if JS fails, the browser must never put the message into a GET URL. --}}
                <form id="form-kirim" method="post" class="d-flex gap-2 mt-2">
                    <label for="isi-pesan" class="visually-hidden">Pesan</label>
                    <textarea id="isi-pesan" name="body" class="form-control" rows="2" maxlength="2000" required
                              placeholder="Tulis pesan… (Enter kirim, Shift+Enter baris baru)"></textarea>
                    <button class="btn btn-primary px-4">Kirim</button>
                </form>
                <div id="galat" class="text-danger small mt-1" role="status"></div>
            @else
                <div id="keterangan" class="alert alert-secondary mt-2 mb-0">
                    {{ $keterangan }}
                    {{-- The listener may end the session, so the user also needs a way to the rating page. --}}
                    @can('rate', $session)
                        <a href="{{ route('ratings.create', $session) }}" class="alert-link ms-1">Beri rating</a>
                    @endcan
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card border-danger">
                <div class="card-body">
                    <h2 class="h6 fw-bold text-danger">Kontak krisis</h2>
                    <div id="pesan-krisis" class="alert alert-danger d-none" role="alert" tabindex="-1">
                        Kamu tidak sendirian. Kalau kamu berpikir untuk menyakiti diri atau sedang dalam bahaya,
                        hubungi salah satu kontak di bawah sekarang juga.
                    </div>
                    @if ($saya->id === $session->user_id)
                        <button type="button" id="tombol-darurat" class="btn btn-danger w-100 mb-3">Saya butuh bantuan segera</button>
                    @endif
                    @include('partials.kontak-krisis')
                    <a href="{{ route('krisis') }}" class="small">Buka halaman bantuan darurat</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    {{-- Js::from, not @json: @json splits its argument on commas and breaks array literals. --}}
    const urlPesan = {{ Js::from(route('chat.messages', $session)) }};
    const urlKirim = {{ Js::from(route('chat.store', $session)) }};
    const nama = {{ Js::from(['pengguna' => $session->user->name, 'listener' => $session->listener->name]) }};
    const pembaca = {{ Js::from(! $peserta) }};
    const terbuka = ['scheduled', 'active'];
    const awalTerbuka = terbuka.includes({{ Js::from($session->status) }});
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const kotak = document.getElementById('daftar-pesan');
    const form = document.getElementById('form-kirim');
    const galat = document.getElementById('galat');
    let lastId = 0;
    let aktif = awalTerbuka;

    const jam = (iso) => new Date(iso).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' });

    function tampil(m) {
        if (document.getElementById('m' + m.id)) return; // send response and poll can overlap
        document.getElementById('kosong')?.remove();
        // Participants: own messages on the right. Psychologist reader: listener on the right.
        const kanan = pembaca ? m.dari === 'listener' : m.punya_saya;

        const baris = document.createElement('div');
        baris.id = 'm' + m.id;
        baris.dataset.id = m.id;
        baris.className = 'd-flex mb-2 ' + (kanan ? 'justify-content-end' : 'justify-content-start');

        const bubble = document.createElement('div');
        bubble.className = 'px-3 py-2 rounded-3 ' + (kanan ? 'bg-primary text-white' : 'bg-white border');
        bubble.style.maxWidth = '75%';
        bubble.style.whiteSpace = 'pre-wrap';
        bubble.style.overflowWrap = 'anywhere';
        bubble.textContent = m.body; // textContent, never innerHTML: the body is user input
        if (!pembaca) {
            // Screen readers cannot see left/right, so name the sender.
            const siapa = document.createElement('span');
            siapa.className = 'visually-hidden';
            siapa.textContent = (m.punya_saya ? 'Kamu' : nama[m.dari]) + ': ';
            bubble.prepend(siapa);
        }

        const info = document.createElement('div');
        info.className = 'small opacity-75 mt-1';
        info.textContent = (pembaca ? nama[m.dari] + ' · ' : '') + jam(m.created_at);
        if (m.flagged) {
            const badge = document.createElement('span');
            badge.className = 'badge text-bg-warning ms-2';
            badge.textContent = 'kata kunci krisis';
            info.append(badge);
        }

        bubble.append(info);
        baris.append(bubble);
        // Insert by id: a sent message can arrive before an older one from the other side.
        const sesudah = [...kotak.children].find((el) => Number(el.dataset.id) > m.id);
        kotak.insertBefore(baris, sesudah || null);
    }

    function tampilBanyak(list, paksaTurun) {
        const diBawah = kotak.scrollHeight - kotak.scrollTop - kotak.clientHeight < 80;
        list.forEach(tampil);
        if (list.length && (diBawah || paksaTurun)) kotak.scrollTop = kotak.scrollHeight;
    }

    function tunjukkanKrisis() {
        const k = document.getElementById('pesan-krisis');
        k.classList.remove('d-none');
        k.scrollIntoView({ behavior: 'smooth', block: 'center' });
        k.focus({ preventScroll: true });
    }

    async function ambil() {
        try {
            const res = await fetch(urlPesan + '?after=' + lastId, { headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            tampilBanyak(data.pesan, lastId === 0);
            // Only polling moves lastId. Moving it on send would skip the other side's
            // message that arrived in between (it has a lower id than ours).
            if (data.pesan.length) lastId = data.pesan[data.pesan.length - 1].id;
            // The other side ended or escalated the session: reload to show the locked state.
            if (awalTerbuka && !terbuka.includes(data.status)) {
                aktif = false;
                location.reload();
            }
        } catch (e) {
            // Network hiccup: the next tick tries again.
        }
    }

    async function loop() {
        await ambil();
        if (aktif) setTimeout(loop, 3000);
    }

    document.getElementById('tombol-darurat')?.addEventListener('click', tunjukkanKrisis);

    if (form) {
        const input = form.elements.body;
        const tombol = form.querySelector('button');

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
                e.preventDefault();
                form.requestSubmit();
            }
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const teks = input.value.trim();
            if (!teks || tombol.disabled) return;
            tombol.disabled = true;
            galat.textContent = '';
            try {
                const res = await fetch(urlKirim, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ body: teks }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    galat.textContent = data.message || 'Pesan gagal dikirim. Coba lagi.';
                    return;
                }
                input.value = '';
                tampilBanyak([data.pesan], true);
                if (data.krisis) tunjukkanKrisis();
            } catch (e) {
                galat.textContent = 'Koneksi terputus. Coba kirim lagi.';
            } finally {
                tombol.disabled = false;
                input.focus();
            }
        });
    }

    loop();
})();
</script>
@endsection

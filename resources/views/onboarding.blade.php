@extends('layouts.app')

{{--
    Onboarding: one small question per screen, Dengi talks, every step can be skipped.
    Without JavaScript all steps simply show one under another and the form still works.
--}}
@section('content')
<div class="container" style="max-width: 560px">
    <form method="POST" action="{{ route('onboarding.store') }}" id="onboarding" class="text-center">
        @csrf

        <div class="onb-dots mb-4" aria-hidden="true">
            @for ($i = 0; $i < 5; $i++)<span></span>@endfor
        </div>

        <div class="d-flex justify-content-center mb-4">
            <x-dengi size="150" class="flex-column">
                <span id="dengi-says">Hai! Aku Dengi 🍎<br>Aku yang menyambutmu di sini.</span>
            </x-dengi>
        </div>

        {{-- 1. Hello --}}
        <section class="onb-step" data-say="Hai! Aku Dengi 🍎<br>Aku yang menyambutmu di sini.">
            <h1 class="h3 mb-2">Selamat datang di Ruang Dengar</h1>
            <p class="text-body-secondary mb-4">Boleh kenalan sebentar? Cuma 3 pertanyaan kecil.</p>
            <button type="button" class="btn btn-primary btn-lg px-5" data-next>Boleh!</button>
        </section>

        {{-- 2. Nickname --}}
        <section class="onb-step" data-say="Aku mau panggil kamu apa?">
            <label for="nickname" class="h4 d-block mb-3">Nama panggilanmu</label>
            <input id="nickname" name="nickname" maxlength="40" value="{{ old('nickname', auth()->user()->displayName()) }}"
                   class="form-control form-control-lg text-center mb-3 @error('nickname') is-invalid @enderror">
            @error('nickname') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <button type="button" class="btn btn-primary btn-lg px-5" data-next>Lanjut</button>
        </section>

        {{-- 3. Mood --}}
        <section class="onb-step" data-say="Gimana perasaanmu belakangan ini?">
            <fieldset>
                <legend class="h4 mb-3">Belakangan ini aku merasa…</legend>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    @foreach ($moods as $kode => [$emoji, $label, $balasan])
                        <input type="radio" class="btn-check" name="mood" id="mood-{{ $kode }}" value="{{ $kode }}"
                               data-reply="{{ $balasan }}" @checked(old('mood') === $kode)>
                        <label class="btn btn-choice btn-mood" for="mood-{{ $kode }}">
                            <span class="fs-2 d-block">{{ $emoji }}</span>{{ $label }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <button type="button" class="btn btn-primary btn-lg px-5 mt-4" data-next>Lanjut</button>
        </section>

        {{-- 4. Topic --}}
        <section class="onb-step" data-say="Biasanya, apa yang paling ingin kamu ceritakan?">
            <fieldset>
                <legend class="h4 mb-3">Yang paling sering kupikirkan</legend>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    @foreach ($topics as $kode => $label)
                        <input type="radio" class="btn-check" name="preferred_topic" id="topik-{{ $kode }}" value="{{ $kode }}"
                               @checked(old('preferred_topic') === $kode)>
                        <label class="btn btn-choice" for="topik-{{ $kode }}">{{ $label }}</label>
                    @endforeach
                </div>
            </fieldset>
            <button type="button" class="btn btn-primary btn-lg px-5 mt-4" data-next>Lanjut</button>
        </section>

        {{-- 5. How it works --}}
        <section class="onb-step" data-say="Begini cara kerjanya, gampang kok!">
            <div class="d-grid gap-2 text-start mb-3">
                <div class="card"><div class="card-body d-flex align-items-center gap-3 py-3">
                    <span class="icon-bubble bg-sage-soft" aria-hidden="true">💬</span>
                    <div><strong>Tekan "Bicara sekarang"</strong><br><small class="text-body-secondary">atau pilih jadwal yang cocok.</small></div>
                </div></div>
                <div class="card"><div class="card-body d-flex align-items-center gap-3 py-3">
                    <span class="icon-bubble bg-peach-soft" aria-hidden="true">🍎</span>
                    <div><strong>Aku carikan teman cerita</strong><br><small class="text-body-secondary">listener terverifikasi, seumuran didahulukan.</small></div>
                </div></div>
                <div class="card"><div class="card-body d-flex align-items-center gap-3 py-3">
                    <span class="icon-bubble bg-lavender-soft" aria-hidden="true">🔒</span>
                    <div><strong>Ngobrol dengan tenang</strong><br><small class="text-body-secondary">chat terenkripsi, admin pun tidak bisa membaca.</small></div>
                </div></div>
            </div>
            <p class="small text-body-secondary">
                Kalau sedang dalam bahaya, tekan <span class="text-danger fw-bold">Bantuan darurat</span> di atas kapan saja.
            </p>
            <button type="submit" class="btn btn-primary btn-lg px-5">Selesai, yuk mulai! 🌷</button>
        </section>

        <button type="submit" class="btn btn-link text-body-secondary mt-3" id="lewati">Lewati dulu</button>
    </form>
</div>

<script>
    // Step-by-step mode. Without this script every step is visible and the form still submits normally.
    (() => {
        const form = document.getElementById('onboarding');
        const steps = [...form.querySelectorAll('.onb-step')];
        const dots = [...form.querySelectorAll('.onb-dots span')];
        const bubble = document.getElementById('dengi-says');
        let current = 0;

        const show = (index) => {
            current = index;
            steps.forEach((step, i) => step.hidden = i !== index);
            dots.forEach((dot, i) => dot.classList.toggle('active', i <= index));
            bubble.innerHTML = steps[index].dataset.say;
            steps[index].querySelector('input, button')?.focus({ preventScroll: true });
        };

        form.classList.add('js-steps');
        form.querySelectorAll('[data-next]').forEach((button) =>
            button.addEventListener('click', () => show(Math.min(current + 1, steps.length - 1))));

        // Dengi answers the mood right away, then moves on.
        form.querySelectorAll('input[name="mood"]').forEach((radio) =>
            radio.addEventListener('change', () => {
                bubble.textContent = radio.dataset.reply;
                setTimeout(() => show(current + 1), 1600);
            }));

        // Enter in the nickname field goes to the next step instead of submitting the whole form.
        document.getElementById('nickname').addEventListener('keydown', (event) => {
            if (event.key === 'Enter') { event.preventDefault(); show(current + 1); }
        });

        show(0);
    })();
</script>
@endsection

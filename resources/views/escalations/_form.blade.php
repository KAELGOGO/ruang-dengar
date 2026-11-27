{{-- Module 4: "Oper ke psikolog" button + reason form, shared by sessions/_aksi-modul-4 and chat/_eskalasi.
     $sesi = CounselingSession, $id = unique element id, $mengambang = float the form over the page (chat header). --}}
@php
    // Several of these forms can be on one page: reopen only the one that failed validation.
    $gagal = $errors->has('reason') && (int) old('from_session') === $sesi->id;
@endphp
<div @class(['position-relative', 'w-100' => ! $mengambang])>
    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="collapse" data-bs-target="#{{ $id }}"
            aria-expanded="{{ $gagal ? 'true' : 'false' }}" aria-controls="{{ $id }}">
        Oper ke psikolog
    </button>

    <div id="{{ $id }}" @class(['collapse', 'show' => $gagal, 'position-absolute end-0 mt-1 z-3' => $mengambang])
         @if ($mengambang) style="width: min(22rem, 90vw)" @endif>
        <form method="POST" action="{{ route('escalations.store', $sesi) }}" class="card card-body shadow-sm mt-2"
              onsubmit="return confirm('Oper kasus ini ke psikolog?')">
            @csrf
            <input type="hidden" name="from_session" value="{{ $sesi->id }}">
            <label for="{{ $id }}-reason" class="form-label fw-semibold">Kenapa perlu psikolog?</label>
            <textarea id="{{ $id }}-reason" name="reason" rows="3" maxlength="1000" required
                      class="form-control @if ($gagal) is-invalid @endif"
                      placeholder="Contoh: pengguna menyebut ingin menyakiti diri dan butuh penanganan profesional.">{{ $gagal ? old('reason') : '' }}</textarea>
            @if ($gagal)
                <div class="invalid-feedback">{{ $errors->first('reason') }}</div>
            @endif
            <div class="form-text">
                Hanya psikolog yang mengambil kasus ini yang bisa membaca alasannya. Kalau ada psikolog yang kosong,
                pengguna langsung dipasangkan di sesi baru. Kalau tidak, kasus masuk antrean dan kamu tetap menemani pengguna.
            </div>
            <button type="submit" class="btn btn-warning mt-2">Oper sekarang</button>
        </form>
    </div>
</div>

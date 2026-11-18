# Pembagian tugas: 4 modul

Setiap orang pegang satu modul dan hanya mengubah file miliknya. Kerangka (database, login, role,
matching, booking, hak akses) sudah jadi, jadi semua bisa mulai sekarang tanpa saling menunggu.
Cara setup dan akun demo ada di [README](../README.md).

| Modul | Pemegang | Isi singkat | Perkiraan |
|---|---|---|---|
| 1. Verifikasi listener | ____ | Profil listener, upload sertifikat, admin approve/reject + gelar | ±6–8 jam |
| 2. Jadwal, sesi & tampilan | ____ | Slot listener, halaman Sesi saya, tampilan umum | ±6–8 jam |
| 3. Chat | Kelvin | Ruang chat (polling), kata kunci krisis, akhiri sesi | ±8–10 jam |
| 4. Eskalasi, rating & metrik | ____ | Eskalasi ke psikolog, antrean psikolog, rating, dashboard admin | ±8–10 jam |

## Jadwal

| Tanggal | Target |
|---|---|
| Kamis 26 Nov | Setiap modul sudah punya Pull Request pertama (fitur inti jalan, boleh belum rapi) |
| Minggu 30 Nov | **Semua modul di-merge ke `main`.** Modul yang belum ada PR saat itu diambil alih Kelvin supaya tidak menghambat yang lain |
| 1–6 Des | Gabung semua, jalankan skenario demo dari awal sampai akhir, perbaiki bug dan tampilan |
| 7 Des – deadline | Laporan, screenshot, latihan presentasi |

---

## Modul 1: Verifikasi listener

Alur kanan di flowchart: listener upload dokumen, admin memeriksa, lalu listener mendapat gelar.

**Sudah ada:** registrasi dengan role dan tanggal lahir, tabel `listener_profiles` dan `listener_documents`,
semua route `listener.profile.*`, `listener.documents.store`, dan `admin.verifications.*`, serta link di navbar.

**File milikmu:** `app/Http/Controllers/Listener/ProfileController.php`,
`app/Http/Controllers/Admin/VerificationController.php`, view baru di `resources/views/listener/profile*`
dan `resources/views/admin/verifications*`.

**Tugas:**
- [ ] Halaman Profil listener: status verifikasi dan gelar, form bio, daftar dokumen yang sudah diupload beserta statusnya, form upload (pdf/jpg/png, maksimal 5 MB).
- [ ] `update`: simpan bio.
- [ ] `upload`: simpan file dengan `$request->file('document')->store('credentials')` (disk `local` = `storage/app/private`, tidak bisa dibuka dari browser), lalu buat `ListenerDocument`. Kalau profil sebelumnya `rejected`, kembalikan ke `pending`.
- [ ] Admin `index`: daftar profil `pending` (nama, umur, kelompok umur, dokumen) dengan link unduh dokumen.
- [ ] `document`: unduh lewat `Storage::disk('local')->download(...)`. Route-nya sudah khusus admin.
- [ ] `approve`: admin memilih gelar (`volunteer` / `psych_student` / `psychologist`), isi `verified_by` dan `verified_at`, dokumen ditandai approved.
- [ ] `reject`: catatan wajib diisi, dan listener melihat catatan itu di profilnya.

**Selesai kalau:** akun `pending@ruangdengar.test` upload dokumen, lalu admin approve sebagai relawan, dan beranda listener itu berubah jadi terverifikasi.

**Tes minimal:** upload tersimpan di disk private (pakai `Storage::fake('local')`), approve mengubah status, pengguna biasa dapat 403 di halaman admin.

---

## Modul 2: Jadwal, sesi & tampilan

Bagian booking sudah jadi (`BookingController` + `ListenerMatcher`). Modul ini membuat jadwal listener
dan merapikan semua halaman.

**File milikmu:** `app/Http/Controllers/Listener/SlotController.php`, `app/Http/Controllers/SessionController.php`,
`resources/views/sessions/index.blade.php`, `resources/views/booking/*`, `welcome.blade.php`,
`home.blade.php`, `layouts/app.blade.php`, view baru di `resources/views/listener/slots*`, `resources/sass/*`.

**Tugas:**
- [ ] Halaman Jadwal listener: daftar slot mendatang (yang sudah dibooking diberi tanda) dan form tambah slot (tanggal, jam mulai, jam selesai).
- [ ] `store`: tolak slot yang bertumpuk dengan slot listener itu sendiri, dan batasi durasinya (misalnya 30–120 menit).
- [ ] `destroy`: hanya slot milik sendiri yang belum dibooking.
- [ ] Sesi saya: pisahkan "Akan datang" dan "Riwayat", tampilkan gelar listener, dan minta konfirmasi sebelum membatalkan.
- [ ] Halaman booking: kalau tidak ada listener di jam itu, tampilkan jadwal tersedia terdekat (opsional).
- [ ] Tampilan umum: landing page, beranda per role, warna dan logo yang konsisten, rapi di HP.

**Selesai kalau:** relawan membuat slot jam X, lalu pengguna se-tier booking jam X, dan sesinya muncul di "Sesi saya" kedua akun. Slot yang sudah dibooking tidak bisa dihapus.

**Tes minimal:** slot bertumpuk ditolak, dan slot yang sudah dibooking tidak bisa dihapus.

---

## Modul 3: Chat

**Sudah ada:** route `chat.show`, `chat.messages`, `chat.store`, dan `chat.end`, aturan akses (hanya kedua
peserta, ditambah psikolog yang menangani eskalasi), model `Message` dengan isi terenkripsi, daftar kata
kunci di `config/krisis.php`, partial `partials/kontak-krisis`, dan `Escalator::byKeyword()` dari Modul 4.

**File milikmu:** `app/Http/Controllers/ChatController.php`, view baru di `resources/views/chat/show.blade.php`,
dan JavaScript chat (boleh langsung di view itu, atau di `resources/js/chat.js`).

**Tugas:**
- [ ] Halaman chat: bubble pesan kiri/kanan, form kirim, panel kontak krisis (`@include('partials.kontak-krisis')`), tombol "Saya butuh bantuan segera", dan `@include('chat._eskalasi', ['sesi' => $session])` untuk tombol eskalasi dari Modul 4.
- [ ] Polling: setiap 3 detik `fetch` ke `chat.messages?after=<id terakhir>`, lalu tambahkan pesan baru. Kirim pesan juga lewat `fetch` (token CSRF ada di `<meta name="csrf-token">`), tanpa reload halaman.
- [ ] `messages`: kembalikan JSON pesan dengan id lebih besar dari `after`, berisi `id`, `body`, `created_at`, dan `punya_saya`.
- [ ] `store`: periksa kata kunci (huruf kecil, `str_contains`) **sebelum** pesan disimpan, isi `flagged`, lalu panggil `app(\App\Services\Escalator::class)->byKeyword(...)`. Setelah itu tampilkan kontak krisis ke pengirim.
- [ ] Jam aktif: pesan hanya bisa dikirim dari `scheduled_at` sampai `slot->ends_at`. Di luar jam itu, form dikunci dan diberi keterangan. Pesan pertama mengubah status dari `scheduled` ke `active` dan mengisi `started_at`.
- [ ] `end`: status jadi `ended` dan `ended_at` diisi. Pengguna diarahkan ke halaman rating, listener ke "Sesi saya".
- [ ] Psikolog yang membuka chat dari antrean eskalasi hanya bisa membaca (form kirim disembunyikan).

**Selesai kalau:** dua browser (pengguna dan listener) bisa saling kirim pesan tanpa reload, dan pesan yang mengandung "ingin mati" ditandai.

**Tes minimal:** kirim pesan di luar jam ditolak, kata kunci membuat `flagged` jadi true, dan isi pesan di database tidak bisa dibaca (`DB::table('messages')->value('body')` tidak sama dengan teks aslinya).

---

## Modul 4: Eskalasi, rating & metrik

**Sudah ada:** route, aturan akses `escalate` dan `rate`, gate `handle-escalations` (khusus psikolog),
kerangka `app/Services/Escalator.php`, dan partial `sessions/_aksi-modul-4` yang sudah berisi tombol rating.

**File milikmu:** `app/Services/Escalator.php`, `app/Http/Controllers/EscalationController.php`,
`app/Http/Controllers/RatingController.php`, `app/Http/Controllers/Admin/MetricsController.php`,
`resources/views/sessions/_aksi-modul-4.blade.php`, `resources/views/chat/_eskalasi.blade.php`,
dan view baru di `resources/views/escalations*`, `ratings*`, serta `admin/metrics*`.

**Tugas:**
- [ ] `Escalator::byListener` dan `byKeyword`. Baca komentar di file itu dan **jangan ubah nama method-nya**, karena Modul 3 memanggilnya.
- [ ] Tombol "Perlu tindakan lanjut?" untuk listener, berisi form alasan. Tombolnya ada di partial `_aksi-modul-4` dan `chat/_eskalasi`, dan mengirim ke `EscalationController@store`.
- [ ] Antrean psikolog: daftar kasus `open` dan `assigned`, kasus dari kata kunci paling atas, tombol "Ambil kasus" dan "Selesai", serta link ke chat sesi itu.
- [ ] Rating: form 1–5 dan komentar, satu kali per sesi.
- [ ] Dashboard metrik admin:
  - jumlah sesi selesai;
  - *repeat usage* (pengguna dengan lebih dari 1 sesi);
  - jumlah eskalasi (dari listener dan dari kata kunci);
  - rata-rata rating.

  Ini bukti "measurable social outcomes" di proposal. Kalau mau grafik, pakai progress bar Bootstrap saja, karena lab belum tentu ada internet.

**Selesai kalau:** listener mengeskalasi sebuah sesi, lalu psikolog mengambil dan menyelesaikannya, pengguna memberi rating, dan angka di dashboard metrik ikut berubah.

**Tes minimal:** hanya listener sesi itu yang bisa mengeskalasi, rating kedua kali ditolak, dan `byKeyword` tidak membuat eskalasi ganda.

---

## Aturan bersama

- **Branch per modul:** `modul-1-verifikasi`, `modul-2-jadwal`, `modul-3-chat`, `modul-4-eskalasi`. Selesai, buka Pull Request ke `main`.
- **Hanya ubah file milikmu.** Kalau butuh perubahan di file orang lain, kabari pemiliknya. Hubungan antarmodul sudah disiapkan lewat partial dan `Escalator`, jadi seharusnya jarang perlu.
- **`routes/web.php` dan `layouts/app.blade.php`:** semua route dan link sudah ada. Kalau butuh route baru, tambahkan di bagian modulmu saja.
- **Database:** jangan mengubah migration lama. Kalau butuh kolom baru, buat migration baru.
- **Data demo:** buat seeder sendiri (misalnya `Modul3Seeder`), lalu tambahkan satu baris `$this->call(...)` di akhir `DatabaseSeeder`.
- **Sebelum PR:** `php artisan test` harus hijau, dan modulmu dicoba sendiri dengan akun demo.
- **Boleh pakai AI, tapi pahami kodenya.** Saat presentasi, dosen bisa menanyakan bagian masing-masing.

## Skenario demo akhir (urutan presentasi)

1. Calon listener daftar, upload sertifikat, lalu admin memverifikasinya dan memberi gelar. **(Modul 1)**
2. Listener membuat jadwal. **(Modul 2)**
3. Pengguna remaja menekan "Bicara sekarang" dan dipasangkan dengan psikolog. Pengguna 20 tahun dipasangkan dengan relawan seumuran. **(kerangka)**
4. Pengguna dan listener mengobrol. Satu pesan berisi kata kunci krisis memunculkan kontak darurat. **(Modul 3)**
5. Listener mengeskalasi sesi, lalu psikolog mengambil kasusnya. Pengguna memberi rating. **(Modul 4)**
6. Admin membuka dashboard metrik. **(Modul 4)**

# Ruang Dengar (nama sementara)

Platform peer-support kesehatan mental untuk tugas Web Programming. Pengguna dipasangkan dengan
listener terverifikasi sesuai kelompok umur. Kalau kasusnya berat, listener mengeskalasi ke psikolog.
Isi dan alurnya mengikuti proposal tim (dokumen "WebProg - Background").

**Status:** kerangka. Yang sudah jalan: database, login/registrasi dengan tanggal lahir dan role,
aturan matching, booking "Bicara sekarang" / jadwal, daftar sesi, hak akses, dan akun demo.
Sisanya dibagi per modul di bawah. Setiap halaman yang belum dibuat menampilkan daftar TODO-nya.

## Setup (Windows + XAMPP)

Butuh PHP 8.2+, Composer, Node.js. Database: SQLite, jadi tidak perlu MySQL.

```bash
git clone https://github.com/KAELGOGO/ruang-dengar.git
cd ruang-dengar
composer install
copy .env.example .env
php artisan key:generate
type nul > database\database.sqlite
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Buka http://localhost:8000. Di `.env`, ubah `APP_NAME=Laravel` menjadi `APP_NAME="Ruang Dengar"`.

- Reset database + data demo: `php artisan migrate:fresh --seed`
- Jalankan tes: `php artisan test` (wajib hijau sebelum push)
- Saat ngoding tampilan: `npm run dev` di terminal kedua, supaya CSS/JS langsung ter-update

### Akun demo (hanya lokal)

Password semua akun demo: `password`.

| Email | Peran |
|---|---|
| admin@ruangdengar.test | Admin |
| remaja@ruangdengar.test | Pengguna 15 tahun (hanya bisa dapat psikolog) |
| muda@ruangdengar.test | Pengguna 20 tahun |
| dewasa@ruangdengar.test | Pengguna 30 tahun |
| relawan@ruangdengar.test | Listener relawan, 20 tahun, terverifikasi |
| mahasiswa@ruangdengar.test | Listener mahasiswa psikologi, 22 tahun, terverifikasi |
| psikolog@ruangdengar.test | Psikolog, terverifikasi, bisa membuka antrean eskalasi |
| pending@ruangdengar.test | Listener yang belum diverifikasi admin |

Setiap listener terverifikasi punya satu slot yang sedang berjalan (untuk demo "Bicara sekarang")
dan slot jam 19.00 untuk 3 hari ke depan.

## Keputusan desain (dan alasannya, untuk laporan)

1. **Aturan matching** (`app/Services/ListenerMatcher.php`, dites di `tests/Feature/MatchingTest.php`):
   - Hanya listener yang sudah di-approve admin.
   - Relawan dan mahasiswa psikologi hanya melayani pengguna dari kelompok umur yang sama.
   - Psikolog boleh melayani semua kelompok umur.
   - Listener minimal 18 tahun, jadi remaja 13–17 tahun hanya dilayani psikolog. Ini menjaga
     "age-tiered matching" di proposal tanpa membuat anak menangani anak.
   - Kalau ada beberapa yang cocok, dipilih listener dengan sesi paling sedikit.
2. **Kelompok umur dihitung dari tanggal lahir** (`app/Support/AgeTier.php`), tidak disimpan di
   database. Akibatnya tier tidak bisa basi saat pengguna berulang tahun, dan tidak bisa diubah
   pengguna. Batasan: tanggal lahir diisi sendiri; verifikasi KTP adalah *future work*.
3. **Tabel `counseling_sessions`**, bukan `sessions`, karena `sessions` sudah dipakai Laravel untuk login.
4. **Satu slot tidak bisa dibooking dua kali:** ada unique index di `availability_slot_id` dan
   transaksi database. Membatalkan sesi mengosongkan slot lagi.
5. **Isi chat dienkripsi** (cast `encrypted` di model `Message`). Penanda kata kunci krisis
   (`flagged`) harus diisi **sebelum** pesan disimpan, karena teks terenkripsi tidak bisa dicari.
6. **Admin tidak bisa membaca chat.** Metrik cukup menghitung angka. Yang boleh membaca chat
   hanya kedua peserta dan psikolog yang menangani eskalasinya (`app/Policies/CounselingSessionPolicy.php`).
7. **3 level listener:** relawan, mahasiswa psikologi, psikolog. Ini mengikuti bagian Novelty;
   flowchart hanya menyebut 2 gelar, dan 3 level sudah mencakup keduanya.
8. **"Bicara sekarang" selain booking jadwal**, karena Background proposal menekankan akses tanpa janji temu.
9. **Chat memakai polling** (JavaScript `fetch` tiap 2–3 detik), bukan WebSocket. Tidak perlu
   server tambahan, jadi aman saat demo.
10. **Kontak krisis** ada di `config/krisis.php` dan halaman `/bantuan-darurat`. Isinya Healing119
    Kemenkes (119 ext 8), dicek 19 Nov 2026. Cek ulang sebelum presentasi.

## Pembagian modul

Setiap modul punya controller dengan komentar `TODO (Module N)`. Cari dengan `TODO (Module 1)` dan seterusnya.

| Modul | Isi | File utama |
|---|---|---|
| 1. Auth & verifikasi listener | Profil listener, upload sertifikat (disk private), admin approve/reject + pilih gelar | `Listener/ProfileController`, `Admin/VerificationController` |
| 2. Booking & jadwal | Slot ketersediaan listener (tambah/hapus, tolak yang bentrok), rapikan halaman sesi | `Listener/SlotController`, `SessionController`, `BookingController` (sudah jalan) |
| 3. Chat | Halaman chat + polling, simpan pesan + penanda kata kunci, akhiri sesi, panel kontak krisis | `ChatController`, `partials/kontak-krisis.blade.php` |
| 4. Eskalasi, rating, metrik | Tombol eskalasi listener, antrean psikolog, form rating, dashboard metrik admin | `EscalationController`, `RatingController`, `Admin/MetricsController` |

## Aturan kerja Git

- Jangan kerja langsung di `main`. Buat branch per modul, misalnya `modul-3-chat`, lalu buka Pull Request.
- Jangan pernah commit `.env` (sudah di-.gitignore).
- **Jangan mengubah migration yang sudah ada di `main`.** Kalau butuh kolom baru, buat migration baru
  (`php artisan make:migration add_xxx_to_yyy_table`), supaya database teman lain tidak rusak.
- Jalankan `php artisan test` sebelum push.

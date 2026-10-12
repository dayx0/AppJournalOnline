# Laporan Audit — AppJournalOnline

> Stack: Laravel 13 + Inertia + Fortify | Tanggal: 2026-09-20
> Cakupan: celah keamanan, kode berpotensi bug, kode tidak digunakan.

## Ringkasan Eksekutif

| Kategori | Temuan Kritis | Keterangan |
|---|---|---|
| Keamanan | 3 | Dump DB ter-commit, `verified` no-op, grup `/jurnal` tanpa `verified` |
| Keamanan sedang/rendah | 7 | IDOR-by-design, fillable luas, admin user mgmt, CSV injection, over-share props |
| Bug potensial | 9 | Validasi jam/tanggal/absensi, notifikasi stale, audit trail bolong |
| Kode mati | 6 | Method/import/halaman tidak terpakai |

---

## 1. Celah Keamanan

### 1.1 Kritis

#### [K-1] Dump database produksi ter-commit ke git
- **Lokasi:** `backup_appjournalonline_20260916.sql:436`
- **Bukti:** berisi `INSERT INTO users` dengan 15 akun + hash bcrypt `$2y$12$...`, nama, dan email asli.
- **Dampak:** PII bocor ke siapa pun yang punya akses repo; hash bisa di-crack offline.
- **Perbaikan:**
  1. Hapus dari history: `git filter-repo --path backup_appjournalonline_20260916.sql --invert-path`
  2. Tambahkan `*.sql` dan `*.dump` ke `.gitignore`.
  3. Anggap semua password bocor — paksa reset password.
  4. Jangan simpan backup di repo; pakai storage privat/terenkripsi.

#### [K-2] Middleware `verified` tidak berfungsi (no-op)
- **Lokasi:** `app/Models/User.php:6,35`, `routes/web.php:23,99,119`, `config/fortify.php:166`
- **Bukti:** `use MustVerifyEmail` di-comment; model tidak implement interface tersebut. `EnsureEmailIsVerified` hanya menolak jika `$user instanceof MustVerifyEmail`, sehingga semua request lolos.
- **Dampak:** seluruh gate `verified` mati; fitur `Features::emailVerification()` tidak pernah mengirim email verifikasi yang bermakna.
- **Perbaikan:** pilih satu —
  - Opsi A: `class User extends Authenticatable implements MustVerifyEmail` + kirim notifikasi verifikasi, atau
  - Opsi B: cabut `verified` dari semua route dan matikan `Features::emailVerification()` jika memang tidak butuh.

#### [K-3] Grup `/jurnal/*` tanpa `verified`
- **Lokasi:** `routes/web.php:79`
- **Bukti:** `Route::middleware(['auth', 'isGuruOrAdmin'])` sedangkan `/dashboard` memakai `['auth', 'verified']`.
- **Dampak:** user belum verifikasi bisa create/read/update/delete jurnal (setelah K-2 diperbaiki, ini tetap bolong).
- **Perbaikan:** samakan menjadi `['auth', 'verified', 'isGuruOrAdmin']`.

### 1.2 Sedang

#### [S-1] Otorisasi `ensureOwns()` ambigu / IDOR-by-design
- **Lokasi:** `app/Http/Controllers/JournalController.php:15-21,25-28`
- **Bukti:** bypass `if (auth()->user()->isAdmin()) return;` dengan komentar "hapus baris ini kalau admin tidak boleh". `index()` filter `guru_id = auth()->id()` sehingga admin melihat list kosong tapi tetap bisa buka ID siapa pun via URL langsung.
- **Perbaikan:** tegaskan kebijakan di ADR/Policy Laravel; jika admin read-only, buat route monitor khusus (jangan pakai bypass di controller guru). Pertimbangkan `JournalPolicy`.

#### [S-2] `$fillable` Journal terlalu luas
- **Lokasi:** `app/Models/Journal.php:18-32`
- **Bukti:** memuat `guru_id, status, validated_by, validated_at, validation_note`. Aman hari ini karena `JournalController.php:77,113` memakai hasil `validate()` (whitelist), tapi satu `request->all()` di masa depan = eskalasi.
- **Perbaikan:** persempit `$fillable` ke field input guru saja; set `status/validated_*` via assignment eksplisit atau `$guarded`. Pindahkan validasi ke FormRequest.

#### [S-3] Manajemen user admin kurang guard
- **Lokasi:** `app/Http/Controllers/Admin/UserController.php:22-59`
- **Bukti:**
  - Bisa demote diri sendiri `admin → guru` (hanya cegah hapus diri di `destroy():36`, tidak di `update()`).
  - Ganti email tanpa reset `email_verified_at` (bandingkan `Settings/ProfileController.php:35-37` yang benar).
  - `password: Password::min(8)` tanpa `confirmed` dan `max` (DoS string raksasa).
  - Tanpa `throttle`.
- **Perbaikan:** tolak perubahan role diri sendiri, set `email_verified_at = null` saat email berubah, pakai `'password' => ['nullable','string','min:8','max:72','confirmed']`, tambah `throttle`.

#### [S-4] CSV formula injection di export rekap
- **Lokasi:** `app/Http/Controllers/Mpk/RekapController.php:105-118`
- **Bukti:** `materi`, `validation_note`, nama guru/kelas/mapel ditulis mentah via `fputcsv(..., ';')`.
- **Dampak:** nilai berawalan `= + - @` dieksekusi sebagai formula saat dibuka di Excel.
- **Perbaikan:** sanitasi tiap sel, mis. prefix `'` jika match `/^[=+\-@]/`, atau strip karakter tersebut.

#### [S-5] Over-share `auth.user` ke frontend
- **Lokasi:** `app/Http/Middleware/HandleInertiaRequests.php:42-44`
- **Bukti:** `'user' => $request->user()` mengirim seluruh atribut model (bergantung pada `$hidden`).
- **Perbaikan:** kirim selektif: `only(['id','name','email','role','email_verified_at'])`.

#### [S-6] Rate limit tidak merata
- **Lokasi:** `routes/web.php:99-128`, `routes/settings.php:23`, `app/Providers/FortifyServiceProvider.php:88-98`
- **Bukti:** login/2FA/passkey sudah di-limit; rute bisnis (`admin/users store`, `mpk validate/revisi`, `jurnal store`, `rekap/export`) tanpa `throttle`.
- **Perbaikan:** tambah `throttle:30,1` (atau sesuai kebutuhan) untuk store/validate/export.

### 1.3 Rendah

| ID | Lokasi | Catatan |
|---|---|---|
| R-1 | `Mpk/JournalValidationController.php:23-30`, `Admin/JournalMonitorController.php:16-18` | `LIKE "%$s%"` via binding (aman dari SQLi) tapi wildcard `%`/`_` tidak di-escape; monitor tanpa validasi input. Escape dengan `addcslashes($s, '%_\\')`. |
| R-2 | `resources/js/components/two-factor-setup-modal.tsx:81` | `dangerouslySetInnerHTML` untuk QR SVG dari server Fortify — aman, dicatat saja. Jangan pakai untuk data user. |
| R-3 | `routes/settings.php:29` | `.well-known/passkey-endpoints` publik — info minimal, OK. |
| R-4 | `.env.example:4` | `APP_DEBUG=true` sebagai contoh — pastikan production `false` dan `SESSION_ENCRYPT` dievaluasi. |

---

## 2. Kode Berpotensi Bug

| ID | Lokasi | Masalah | Perbaikan |
|---|---|---|---|
| B-1 | `JournalController.php:81-82,117-118` | `jam_mulai/jam_selesai` hanya `required`; kolom DB `time` (`2026_08_13_070426_create_journals_table.php:22-23`) error 500 jika input sampah | `date_format:H:i` + `after:jam_mulai` untuk `jam_selesai` |
| B-2 | `JournalController.php:80,116` | `tanggal: required\|date` tanpa batas → jurnal masa depan jauh lolos | tambah `before_or_equal:today` (atau aturan bisnis yang disepakati) |
| B-3 | `JournalController.php:86-89,122-125,179-184` | `hadir/izin/sakit/alpha` hanya `min:0`, tanpa `max`/konsistensi | tambah `max:200` (sesuaikan rombel) + validasi silang; model siswa belum ada (`show():71 students=[]`) |
| B-4 | `JournalController.php:94-99` | `absensi()->create()` selalu dibuat walau semua null → baris kosong | hanya create jika minimal satu terisi, atau skip |
| B-5 | `JournalController.php:145,202` | Edit saat `status=pending` tidak push notifikasi ulang; jika MPK sudah baca, edit tidak memunculkan unread | panggil `notifyMpkNewJournal()` (dengan `updateOrCreate` + reset `read_at`) setiap ada perubahan konten saat pending |
| B-6 | `JournalHistoryService.php:38-40` | `logKeputusan()` bandingkan `status` dengan konstanta `aksi` (kebetulan sama: `revisi`/`divalidasi`) | bandingkan dengan eksplisit `=== 'revisi'` atau petakan status→aksi via match |
| B-7 | `JournalController.php:128,145` | Edit konten saat pending tidak dicatat ke `journal_histories`; hanya `reset_pending` yang dicatat | tambah `logDiperbarui()` untuk perubahan non-status |
| B-8 | `Settings/SecurityController.php:27,32-33` | `select()` tidak memuat `authenticator` tapi diakses di `map` → selalu null | tambahkan kolom ke `select` atau hapus dari props |
| B-9 | `Admin/DashboardController.php:14`, `Admin/JournalMonitorController.php:16` | Import/parameter tidak konsisten; search monitor tanpa validasi & filter tanggal | rapikan import; pakai validasi seperti `RekapController::filters()` |

**Catatan tambahan:**
- Route-model binding `{kls}` (`web.php:108-109` + `ClassRoomController.php:30`) bekerja via type-hint, tapi pastikan frontend Wayfinder mengirim nama param yang sama.
- Export `RekapController::export()` memakai `chunk(500)` dengan eager load dari `baseQuery()` — sudah OK; jangan hilangkan `with()` saat refactor.

---

## 3. Kode Tidak Digunakan / Mati

| ID | Lokasi | Keterangan |
|---|---|---|
| U-1 | `app/Models/UserNotification.php:49` | `markAsRead()` tidak punya pemanggil (hanya definisi). Yang dipakai `markJournalRead()` statis. Hapus atau pakai. |
| U-2 | `app/Http/Controllers/Admin/DashboardController.php:10` | `use Illuminate\Http\Request` tidak dipakai. Hapus. |
| U-3 | `app/Http/Controllers/Controller.php:5-8` | Base class kosong. Isi dengan trait `AuthorizesRequests`/`ValidatesRequests` atau hapus. |
| U-4 | `app/Http/Controllers/Settings/ProfileController.php:23` | `mustVerifyEmail` selalu `false` akibat K-2 → halaman `resources/js/pages/auth/verify-email.tsx` mati. Ikut K-2. |
| U-5 | `backup_appjournalonline_20260916.sql` | Artefak backup di repo, bukan code. Pindahkan ke storage privat. |
| U-6 | Frontend | `resources/js/pages/profil.tsx` vs `settings/profile.tsx` duplikat; `ui/placeholder-pattern.tsx` kemungkinan sisa template. Konfirmasi dengan `npx knip` / `eslint --rule no-unused-vars`. |

---

## 4. Rencana Perbaikan (Prioritas)

1. **Sekarang:** hapus SQL dari history + `*.sql` ke `.gitignore` + paksa reset password.
2. **Auth:** perbaiki `MustVerifyEmail` (atau cabut `verified`), samakan middleware grup jurnal.
3. **Otorisasi:** tegaskan policy admin vs guru; tambah `JournalPolicy` + test IDOR.
4. **Validasi:** jam (`H:i` + `after`), tanggal (`before_or_equal:today`), absensi (`max` + konsistensi), sanitasi CSV.
5. **Notifikasi/audit:** notifikasi ulang saat edit pending; catat semua perubahan konten ke `journal_histories`.
6. **Hygiene:** hapus `markAsRead()` atau pakai; hapus import mati; selektifkan shared props; tambah `throttle`; jalankan `knip` + `pint --test` + `phpstan`.

---

## Lampiran: Perintah Verifikasi

```bash
# Pastikan tidak ada secret/dump terlacak
git ls-files | grep -Ei '\.env$|\.sql$|database\.sqlite'
grep -rn "MustVerifyEmail" app/ config/ routes/

# Cari kode mati
grep -rn "markAsRead()" app/ resources/ routes/ tests/
npx knip

# Cek kualitas
./vendor/bin/pint --test
php artisan test
```

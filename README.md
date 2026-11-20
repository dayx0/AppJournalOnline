# AppJournalOnline

Aplikasi jurnal mengajar dan validasi MPK untuk SMK. Dibangun dengan Laravel, Inertia, React, dan TypeScript.

## Peran pengguna

- **Admin**: mengelola pengguna, kelas, mata pelajaran, jadwal, tahun ajaran, dan memantau jurnal.
- **Guru**: mengisi jurnal mengajar, absensi, dan melihat riwayat validasi.
- **MPK**: memvalidasi, menolak, atau menandai jam kosong untuk kelas yang ditempati.
- **Wali kelas**: mengelola jadwal kelas binaannya.
- **Siswa**: bukan akun login. Data siswa disimpan sebagai master data; absensi per siswa akan memakai data ini.

## Stack

- PHP 8.4.1+, Laravel 13
- MySQL 8 untuk produksi, SQLite untuk lokal/test
- Node.js 22+, React, TypeScript, Vite, Inertia
- Pest untuk test, Larastan/PHPStan level 7, Laravel Pint

## Setup lokal

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run dev
php artisan serve
```

Di Linux/macOS, gunakan `cp .env.example .env`. Pastikan `database/database.sqlite` ada bila memakai SQLite:

```bash
touch database/database.sqlite
php artisan migrate
```

### Data awal

`php artisan db:seed` hanya mengisi data master (jurusan, kelas, mapel, tahun ajaran) dan membuat akun admin awal dari `ADMIN_EMAIL` + `ADMIN_PASSWORD` (minimal 12 karakter). Tidak ada password default.

Untuk data demo lengkap (akun contoh, jadwal acak, simulasi jurnal MPK):

```bash
# Isi DEMO_SEED_PASSWORD (minimal 12 karakter) di .env terlebih dahulu.
php artisan db:seed --class=DemoSeeder
```

`DemoSeeder` menolak berjalan di luar lingkungan `local`/`testing`, tidak menghapus jadwal yang sudah ada, dan hanya mengisi slot jadwal yang masih kosong. Jangan pernah menjalankan seeder demo di server produksi.

## Perintah kualitas

```bash
composer ci:check
```

Perintah tersebut menjalankan:

- ESLint dan Prettier check
- TypeScript check
- PHPStan level 7
- Pest test suite

Perintah terpisah:

```bash
composer lint          # perbaiki format PHP dengan Pint
composer lint:check    # cek format PHP
composer types:check   # PHPStan
php artisan test       # test backend
npm run types:check    # TypeScript
```

## Konfigurasi produksi

Salin `.env.production.example` menjadi `.env`, isi kredensial yang sebenarnya, lalu:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Sebelum `db:seed`, isi `ADMIN_EMAIL` dan `ADMIN_PASSWORD` (minimal 12 karakter). Seeder produksi hanya mengisi data master dan satu akun admin; seeder demo tidak dijalankan. Jangan menjalankan `DemoSeeder` di server.

Gunakan Nginx + PHP-FPM, arahkan document root ke folder `public/`, dan jalankan queue worker serta scheduler sesuai kebutuhan. Jangan mengaktifkan `APP_DEBUG` di produksi.

## Alur kontribusi

1. Buat branch pendek dari `main`, misalnya `feat/absensi-siswa`.
2. Commit perubahan yang fokus dan sertakan test bila mengubah perilaku.
3. Buka pull request ke `main`.
4. Pastikan `composer ci:check` hijau dan minimal satu reviewer menyetujui.
5. Rebase dan merge satu PR dalam satu waktu untuk mengurangi konflik.

## Catatan keamanan

- Registrasi publik dimatikan. Akun hanya dibuat oleh admin melalui menu Kelola User.
- Penghapusan user, kelas, dan mapel ditolak bila masih memiliki relasi data (jurnal, jadwal, siswa, wali kelas, atau validasi). Nonaktifkan akun atau pindahkan relasinya terlebih dahulu.
- File dump `backup_*.sql` tidak lagi dilacak Git dan diabaikan lewat `.gitignore`.
- Dump lama masih ada di riwayat Git. Sebelum repositori dijadikan publik, pindahkan/rotasi kredensial yang pernah ada di dump dan bersihkan riwayat dengan alat seperti `git filter-repo`.

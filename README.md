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
php artisan migrate --seed
npm install
npm run dev
php artisan serve
```

Di Linux/macOS, gunakan `cp .env.example .env`. Pastikan `database/database.sqlite` ada bila memakai SQLite:

```bash
touch database/database.sqlite
php artisan migrate --seed
```

> Seeder saat ini masih memuat data demo. Pemisahan seeder produksi dan demo dilakukan pada paket hardening berikutnya. Jangan menjalankan seeder demo di server produksi.

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
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Gunakan Nginx + PHP-FPM, arahkan document root ke folder `public/`, dan jalankan queue worker serta scheduler sesuai kebutuhan. Jangan mengaktifkan `APP_DEBUG` di produksi.

## Alur kontribusi

1. Buat branch pendek dari `main`, misalnya `feat/absensi-siswa`.
2. Commit perubahan yang fokus dan sertakan test bila mengubah perilaku.
3. Buka pull request ke `main`.
4. Pastikan `composer ci:check` hijau dan minimal satu reviewer menyetujui.
5. Rebase dan merge satu PR dalam satu waktu untuk mengurangi konflik.

## Catatan keamanan

- File dump `backup_*.sql` tidak lagi dilacak Git dan diabaikan lewat `.gitignore`.
- Dump lama masih ada di riwayat Git. Sebelum repositori dijadikan publik, pindahkan/rotasi kredensial yang pernah ada di dump dan bersihkan riwayat dengan alat seperti `git filter-repo`.
- Registrasi publik dan pemisahan seeder produksi akan ditutup pada paket hardening berikutnya.

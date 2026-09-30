<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel profil MPK: setiap akun MPK (users.role = mpk) boleh punya satu
     * baris siswa berisi cermin kelas yang dipegang + NIS. Login tetap di
     * tabel users.
     *
     * CATATAN HASIL MERGE: users.kelas_id DIPERTAHANKAN sebagai satu-satunya
     * sumber penempatan (seluruh controller + test mengandalkannya), jadi
     * penghapusan kolom dari versi awal sudah dibuang. Yang tersisa:
     * buat tabel + salin data MPK yang sudah punya kelas ke sini.
     *
     * Catatan: CREATE memakai satu statement mentah (unique + FK inline)
     * agar tidak ada ALTER terpisah sesudahnya.
     */
    public function up(): void
    {
        if (! Schema::hasTable('siswa')) {
            if (DB::getDriverName() === 'sqlite') {
                // Test (sqlite): pakai schema builder biasa.
                Schema::create('siswa', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                    $table->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
                    $table->string('nis', 50)->nullable()->unique();
                    $table->timestamps();
                });
            } else {
                DB::statement('CREATE TABLE `siswa` (
                    `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `user_id` bigint unsigned NOT NULL,
                    `kelas_id` bigint unsigned NULL,
                    `nis` varchar(50) NULL,
                    `created_at` timestamp NULL,
                    `updated_at` timestamp NULL,
                    UNIQUE KEY `siswa_user_id_unique` (`user_id`),
                    UNIQUE KEY `siswa_nis_unique` (`nis`),
                    KEY `siswa_kelas_id_foreign` (`kelas_id`),
                    CONSTRAINT `siswa_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `siswa_kelas_id_foreign` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            }
        }

        // Salin MPK yang sudah punya kelas ke tabel siswa (cermin data).
        // users.kelas_id TIDAK dihapus — tetap sumber penempatan resmi.
        // Catatan: Query Builder hanya punya updateOrInsert (updateOrCreate
        // itu milik Eloquent), jadi timestamps diisi manual.
        if (Schema::hasColumn('users', 'kelas_id')) {
            $mpk = DB::table('users')
                ->where('role', 'mpk')
                ->whereNotNull('kelas_id')
                ->get(['id', 'kelas_id']);

            foreach ($mpk as $row) {
                DB::table('siswa')->updateOrInsert(
                    ['user_id' => $row->id],
                    ['kelas_id' => $row->kelas_id, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa');
    }
};

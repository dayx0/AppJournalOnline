<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel khusus MPK: setiap akun MPK (users.role = mpk) punya satu baris siswa
     * berisi kelas yang dipegang + NIS. Login tetap di tabel users.
     * Data lama (users.kelas_id) dipindahkan ke sini lalu kolomnya dihapus
     * agar tidak ada dua sumber kebenaran.
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

        // Pindahkan MPK yang sudah punya kelas ke tabel siswa.
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

            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('kelas_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('kelas_id')->nullable()->after('role')->constrained('kelas')->nullOnDelete();
        });

        // Kembalikan kelas ke users.
        $siswa = DB::table('siswa')->get(['user_id', 'kelas_id']);

        foreach ($siswa as $row) {
            DB::table('users')->where('id', $row->user_id)->update(['kelas_id' => $row->kelas_id]);
        }

        Schema::dropIfExists('siswa');
    }
};

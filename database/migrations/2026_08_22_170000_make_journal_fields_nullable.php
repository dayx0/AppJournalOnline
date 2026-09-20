<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Jalankan perubahan ke tabel yang sudah ada.
     *
     * Catatan: sintaks MODIFY hanya dimengerti MySQL. Migrasi pembuat
     * tabelnya sudah mendefinisikan kolom-kolom ini sebagai nullable,
     * jadi di driver lain (mis. sqlite untuk testing) tidak ada yang
     * perlu diubah.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE jurnal MODIFY catatan TEXT NULL');
        DB::statement('ALTER TABLE absensi_jurnal MODIFY hadir INT NULL');
        DB::statement('ALTER TABLE absensi_jurnal MODIFY izin INT NULL');
        DB::statement('ALTER TABLE absensi_jurnal MODIFY sakit INT NULL');
        DB::statement('ALTER TABLE absensi_jurnal MODIFY alpha INT NULL');
    }

    /**
     * Kembalikan seperti semula.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE jurnal MODIFY catatan TEXT NOT NULL');
        DB::statement('ALTER TABLE absensi_jurnal MODIFY hadir INT NOT NULL');
        DB::statement('ALTER TABLE absensi_jurnal MODIFY izin INT NOT NULL');
        DB::statement('ALTER TABLE absensi_jurnal MODIFY sakit INT NOT NULL');
        DB::statement('ALTER TABLE absensi_jurnal MODIFY alpha INT NOT NULL');
    }
};

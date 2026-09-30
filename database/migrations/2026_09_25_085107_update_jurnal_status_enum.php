<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('jurnal')->where('status', 'revisi')->update(['status' => 'pending']);
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE jurnal MODIFY status ENUM(
                'menunggu', 'pending', 'divalidasi', 'ditolak', 'jam_kosong', 'izin', 'terlambat'
            ) NOT NULL DEFAULT 'menunggu'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('jurnal')
            ->whereIn('status', ['menunggu', 'ditolak', 'jam_kosong', 'izin', 'terlambat'])
            ->update(['status' => 'pending']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE jurnal MODIFY status ENUM(
                    'pending', 'divalidasi', 'revisi') NOT NULL DEFAULT 'pending'");
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu MPK menempati satu kelas (banyak MPK boleh satu kelas).
     * Nullable agar MPK yang belum ditempati tetap bisa login,
     * tapi tidak bisa melihat/menvalidasi jurnal apapun.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('kelas_id')
                ->nullable()
                ->after('role')
                ->constrained('kelas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kelas_id');
        });
    }
};

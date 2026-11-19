<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daftar tahun ajaran. Hanya SATU baris is_aktif dalam satu waktu.
     * locked_at terisi = arsip read-only (kunci total, tak bisa dibuka lagi
     * kecuali manual via database — cegah klik ganda / rollback liar).
     */
    public function up(): void
    {
        Schema::create('tahun_ajarans', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 9)->unique()->comment('Contoh: 2025/2026');
            $table->string('semester_aktif', 6)->default('ganjil');
            $table->boolean('is_aktif')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahun_ajarans');
    }
};

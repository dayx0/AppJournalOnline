<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak audit keputusan validasi per jurnal.
     *
     * Aksi yang dicatat: dibuat, divalidasi, revisi, reset_pending
     * (guru mengubah jurnal/absensi sehingga perlu validasi ulang).
     * Riwayat ikut terhapus saat jurnal dihapus (cascade) agar tidak
     * jadi data yatim; penghapusan jurnal sendiri tidak dicatat.
     */
    public function up(): void
    {
        Schema::create('journal_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('jurnal')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            // dibuat | divalidasi | revisi | reset_pending
            $table->string('aksi', 32);
            $table->string('dari_status', 32)->nullable();
            $table->string('ke_status', 32)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['journal_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_histories');
    }
};

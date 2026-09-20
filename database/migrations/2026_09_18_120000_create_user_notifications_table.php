<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Status baca notifikasi per user.
     *
     * Isi (judul/sub/url) tetap derived dari tabel jurnal di frontend,
     * tabel ini hanya menyimpan status baca + pointer (key) per user.
     * Nama tabel dibuat khusus agar tidak bentrok dengan tabel
     * `notifications` bawaan Laravel Notifiable.
     */
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Contoh key: "jurnal.12.pending", "jurnal.12.revisi", "jurnal.12.divalidasi"
            $table->string('key');
            // pending | revisi | divalidasi | info
            $table->string('kind')->default('validasi');
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('url')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained('jurnal')->nullOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'key']);
            $table->index(['user_id', 'read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};

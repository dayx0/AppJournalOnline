<?php

use App\Models\Jurusan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Idempoten: run sebelumnya sempat membuat tabel sebelum gagal,
        // jadi lewati create bila tabel sudah ada, lanjut seed.
        if (! Schema::hasTable('jurusans')) {
            Schema::create('jurusans', function (Blueprint $table) {
                $table->id();
                $table->string('kode', 20)->unique();
                $table->string('nama', 100);
                $table->string('kelompok', 20)->index();
                $table->timestamps();
            });
        }

        // Seed di migrasi (bukan hanya seeder) agar migrate:fresh
        // tanpa --seed tetap punya master untuk backfill kelas.
        foreach (Jurusan::MASTER as $row) {
            Jurusan::firstOrCreate(['kode' => $row['kode']], $row);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('jurusans');
    }
};

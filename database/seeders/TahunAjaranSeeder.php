<?php

namespace Database\Seeders;

use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class TahunAjaranSeeder extends Seeder
{
    /**
     * Data awal tahun ajaran.
     *
     * Hanya mengisi saat tabel masih kosong. Setelah ada data, seeder tidak
     * mengubah tahun aktif atau mengunci arsip karena perubahan tahun ajaran
     * harus lewat menu admin (Tahun Ajaran).
     */
    public function run(): void
    {
        if (TahunAjaran::query()->exists()) {
            $this->command?->info('TahunAjaranSeeder: data sudah ada, tidak diubah.');

            return;
        }

        TahunAjaran::create([
            'nama' => '2025/2026',
            'semester_aktif' => 'genap',
            'is_aktif' => false,
            'locked_at' => now(),
        ]);

        TahunAjaran::create([
            'nama' => '2026/2027',
            'semester_aktif' => 'ganjil',
            'is_aktif' => true,
            'locked_at' => null,
        ]);
    }
}

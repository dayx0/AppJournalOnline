<?php

namespace Database\Seeders;

use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class TahunAjaranSeeder extends Seeder
{
    /**
     * Tahun berjalan aktif, tahun sebelumnya terkunci (arsip read-only).
     * Idempotent: aman dijalankan ulang.
     */
    public function run(): void
    {
        TahunAjaran::updateOrCreate(
            ['nama' => '2025/2026'],
            ['semester_aktif' => 'genap', 'is_aktif' => false, 'locked_at' => now()]
        );

        TahunAjaran::updateOrCreate(
            ['nama' => '2026/2027'],
            ['semester_aktif' => 'ganjil', 'is_aktif' => true, 'locked_at' => null]
        );

        // Pastikan hanya satu yang aktif (jaga dari data manual ganda).
        TahunAjaran::where('nama', '!=', '2026/2027')->update(['is_aktif' => false]);
    }
}

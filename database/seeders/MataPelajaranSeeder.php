<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class MataPelajaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['nama_mapel' => 'Pemrograman Web', 'kode_mapel' => 'PWEB'],
            ['nama_mapel' => 'Matematika', 'kode_mapel' => 'MTK'],
            ['nama_mapel' => 'Bahasa Indonesia', 'kode_mapel' => 'BIN'],
            // Mapel asli kelas XII RPL (dari tabel jadwal sekolah).
            ['nama_mapel' => 'Konsentrasi Keahlian RPL', 'kode_mapel' => 'KK RPL'],
            ['nama_mapel' => 'KIK RPL', 'kode_mapel' => 'KIK RPL'],
            ['nama_mapel' => 'Bahasa Jawa', 'kode_mapel' => 'BJW'],
            ['nama_mapel' => 'PP', 'kode_mapel' => 'PP'],
            ['nama_mapel' => 'Bahasa Inggris', 'kode_mapel' => 'BING'],
            ['nama_mapel' => 'Mapel Pilihan RPL', 'kode_mapel' => 'MP RPL'],
            ['nama_mapel' => 'Pendidikan Agama dan Budi Pekerti', 'kode_mapel' => 'PABP'],
            ['nama_mapel' => 'Bimbingan Konseling', 'kode_mapel' => 'BK'],
            ['nama_mapel' => 'Bahasa Indonesia', 'kode_mapel' => 'BI'],
        ];

        foreach ($data as $item) {
            Subject::firstOrCreate($item);
        }
    }
}

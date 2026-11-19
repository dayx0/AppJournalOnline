<?php

namespace Database\Seeders;

use App\Models\Jadwal;
use App\Models\Journal;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class MataPelajaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * BIN = Bahasa Indonesia, BING = Bahasa Inggris (jangan tertukar).
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
        ];

        foreach ($data as $item) {
            Subject::firstOrCreate($item);
        }

        // Bersihkan duplikat lama: 'BI' juga bernama Bahasa Indonesia.
        // Pindahkan referensi ke BIN lalu hapus baris BI agar kode tunggal.
        $bin = Subject::where('kode_mapel', 'BIN')->first();
        $bi = Subject::where('kode_mapel', 'BI')->first();
        if ($bin && $bi && $bin->id !== $bi->id) {
            Jadwal::where('mapel_id', $bi->id)->update(['mapel_id' => $bin->id]);
            Journal::where('mapel_id', $bi->id)->update(['mapel_id' => $bin->id]);
            $bi->delete();
        }
    }
}

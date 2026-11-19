<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\Jurusan;
use Illuminate\Database\Seeder;

class KelasSeeder extends Seeder
{
    /**
     * 48 kelas: 16 X + 16 XI + 16 XII.
     * X: AKL 1-4, MPLB 1-3, PM 1-3, DKV 1-3, TJKT 1-2, PPLG.
     * XI/XII: AK 1-4, MP 1-3, BD 1, BR 1-2, DKV 1-3, TJKT 1-2, RPL.
     * PPLG/RPL tanpa nomor (warisan: "X PPLG", "XII RPL" dipakai default jadwal).
     */
    public function run(): void
    {
        $rombels = [
            10 => ['AKL' => 4, 'MPLB' => 3, 'PM' => 3, 'DKV' => 3, 'TJKT' => 2, 'PPLG' => 1],
            11 => ['AK' => 4, 'MP' => 3, 'BD' => 1, 'BR' => 2, 'DKV' => 3, 'TJKT' => 2, 'RPL' => 1],
            12 => ['AK' => 4, 'MP' => 3, 'BD' => 1, 'BR' => 2, 'DKV' => 3, 'TJKT' => 2, 'RPL' => 1],
        ];

        foreach ($rombels as $tingkat => $daftar) {
            $romawi = Jurusan::ROMAWI[$tingkat];
            foreach ($daftar as $kode => $jumlah) {
                $jurusan = Jurusan::where('kode', $kode)->firstOrFail();
                for ($i = 1; $i <= $jumlah; $i++) {
                    // Rombel tunggal PPLG/RPL: tanpa angka ("X PPLG", "XI RPL").
                    $nama = $jumlah === 1 && in_array($kode, ['PPLG', 'RPL'], true)
                        ? "{$romawi} {$kode}"
                        : "{$romawi} {$kode} {$i}";

                    ClassRoom::updateOrCreate(
                        ['nama_kelas' => $nama],
                        ['jurusan_id' => $jurusan->id, 'tingkat' => $tingkat]
                    );
                }
            }
        }
    }
}

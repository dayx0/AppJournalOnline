<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\Jadwal;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class JadwalSeeder extends Seeder
{
    /**
     * Jadwal asli kelas XII RPL (dari tabel jadwal sekolah), fokus satu kelas
     * dulu. Kelas lain menyusul — wali masing-masing mengisi via halaman /jadwal.
     *
     * Waktu dibaca dari kepala kolom gambar: jam 1 = 07:00-07:45, dst.
     * Rentang [mulai, selesai) ditulis per blok merged (mis. KK 3-4 = 08:25-09:45).
     */
    public function run(): void
    {
        $guru = User::where('email', 'guru@sekolah.test')->first()
            ?? User::where('role', 'guru')->first();

        if (! $guru) {
            $this->command->warn('JadwalSeeder: tidak ada user guru, lewati.');

            return;
        }

        // Mohamad Jamali (guru Bahasa Inggris) adalah wali kelas XII RPL.
        $wali = User::where('email', 'mj@sekolah.test')->first();
        $xii = ClassRoom::where('nama_kelas', 'XII RPL')->first();

        if (! $xii) {
            $this->command->warn('JadwalSeeder: kelas XII RPL belum ada, lewati.');

            return;
        }

        if ($wali && $xii->wali_kelas_id === null) {
            $xii->update(['wali_kelas_id' => $wali->id]);
        }

        $mapel = Subject::pluck('id', 'kode_mapel')->all();

        // Bersihkan 4 baris dummy seeder lama (Senin jam 08:00/10:00 mapel PWEB)
        // supaya tidak mengotori grid. Kunci persis => data asli wali aman.
        $pweb = $mapel['PWEB'] ?? null;
        if ($pweb) {
            Jadwal::where('hari', 'senin')
                ->whereIn('jam_mulai', ['08:00:00', '08:00', '10:00:00', '10:00'])
                ->where('mapel_id', $pweb)
                ->where('semester', '2026/2027-ganjil')
                ->delete();
        }

        // [hari, jam_mulai, jam_selesai, kode_mapel, email_guru_pengampu]
        // Guru dibaca dari inisial pengampu di gambar jadwal.
        $templates = [
            // Senin
            ['senin', '08:25', '09:45', 'KK RPL', 'ro@sekolah.test'],
            ['senin', '10:00', '12:00', 'KK RPL', 'ro@sekolah.test'],
            ['senin', '12:50', '13:30', 'BK', 'hi@sekolah.test'],
            ['senin', '13:30', '15:30', 'BI', 'dp@sekolah.test'],
            // Selasa
            ['selasa', '07:45', '09:05', 'KK RPL', 'ro@sekolah.test'],
            ['selasa', '10:00', '12:00', 'KIK RPL', 'ahu@sekolah.test'],
            ['selasa', '12:50', '14:10', 'KIK RPL', 'ahu@sekolah.test'],
            ['selasa', '14:10', '15:30', 'BJW', 'ds@sekolah.test'],
            // Rabu
            ['rabu', '07:45', '09:05', 'PP', 'sda@sekolah.test'],
            ['rabu', '09:05', '09:45', 'BING', 'mj@sekolah.test'],
            ['rabu', '10:00', '11:20', 'KK RPL', 'wk@sekolah.test'],
            ['rabu', '11:20', '12:00', 'MP RPL', 'ana@sekolah.test'],
            ['rabu', '12:50', '13:30', 'MP RPL', 'ana@sekolah.test'],
            ['rabu', '13:30', '15:30', 'PABP', 'nf@sekolah.test'],
            // Kamis
            ['kamis', '07:45', '09:05', 'MTK', 'dn@sekolah.test'],
            ['kamis', '09:05', '09:45', 'BING', 'mj@sekolah.test'],
            ['kamis', '10:00', '10:40', 'BING', 'mj@sekolah.test'],
            ['kamis', '10:40', '12:00', 'KK RPL', 'wk@sekolah.test'],
            ['kamis', '12:50', '15:30', 'KK RPL', 'wk@sekolah.test'],
            // Jumat
            ['jumat', '07:45', '09:45', 'KK RPL', 'ro@sekolah.test'],
            ['jumat', '10:00', '10:40', 'KK RPL', 'ro@sekolah.test'],
            ['jumat', '10:40', '12:00', 'MP RPL', 'ana@sekolah.test'],
        ];

        $guruByEmail = User::whereIn('email', array_unique(array_column($templates, 4)))
            ->pluck('id', 'email')->all();

        foreach ($templates as [$hari, $mulai, $selesai, $kode, $email]) {
            if (! isset($mapel[$kode])) {
                $this->command->warn("JadwalSeeder: mapel {$kode} belum ada, lewati.");

                continue;
            }

            if (! isset($guruByEmail[$email])) {
                $this->command->warn("JadwalSeeder: guru {$email} belum ada, lewati.");

                continue;
            }

            // updateOrCreate (bukan firstOrCreate) agar pengampu ikut terkoreksi
            // saat gambar dibaca ulang. Catatan: kalau admin mengubah pengampu
            // via UI lalu seed dijalankan lagi, nilainya kembali ke tabel ini.
            Jadwal::updateOrCreate(
                [
                    'kelas_id' => $xii->id,
                    'hari' => $hari,
                    'jam_mulai' => $mulai,
                ],
                [
                    'mapel_id' => $mapel[$kode],
                    'guru_id' => $guruByEmail[$email],
                    'jam_selesai' => $selesai,
                    'semester' => '2026/2027-ganjil',
                    'aktif' => true,
                ]
            );
        }
    }
}

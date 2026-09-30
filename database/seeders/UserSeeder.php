<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Akun testing semua role (password: `password`, idempotent).
     *
     * - Penempatan MPK memakai users.kelas_id (sumber kebenaran).
     * - Tiap MPK juga dapat baris profil di tabel siswa (NIS + cermin
     *   kelas) agar fitur profil MPK ikut terisi contoh datanya.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin Sekolah',
                'email' => 'admin@sekolah.test',
                'role' => 'admin',
            ],
            [
                'name' => 'Guru Contoh',
                'email' => 'guru@sekolah.test',
                'role' => 'guru',
            ],
            // Guru asli sesuai tabel jadwal XII RPL (inisial pengampu di gambar).
            // Mohamad Jamali = guru Bahasa Inggris SEKALIGUS wali kelas XII RPL.
            // Nama lain memakai inisial + mapel (bisa diganti nama asli via Kelola User).
            ['name' => 'Mohamad Jamali', 'email' => 'mj@sekolah.test', 'role' => 'guru'],
            ['name' => 'Ro (KK RPL)', 'email' => 'ro@sekolah.test', 'role' => 'guru'],
            ['name' => 'AHU (KIK RPL)', 'email' => 'ahu@sekolah.test', 'role' => 'guru'],
            ['name' => 'DS (BJW)', 'email' => 'ds@sekolah.test', 'role' => 'guru'],
            ['name' => 'SDA (PP)', 'email' => 'sda@sekolah.test', 'role' => 'guru'],
            ['name' => 'WK (KK RPL)', 'email' => 'wk@sekolah.test', 'role' => 'guru'],
            ['name' => 'ANA (MP RPL)', 'email' => 'ana@sekolah.test', 'role' => 'guru'],
            ['name' => 'NF (PABP)', 'email' => 'nf@sekolah.test', 'role' => 'guru'],
            ['name' => 'DN (MTK)', 'email' => 'dn@sekolah.test', 'role' => 'guru'],
            ['name' => 'HI (BK)', 'email' => 'hi@sekolah.test', 'role' => 'guru'],
            ['name' => 'DP (BI)', 'email' => 'dp@sekolah.test', 'role' => 'guru'],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'role' => $user['role'],
                    'kelas_id' => null,
                    'email_verified_at' => now(),
                ]
            );
        }

        // MPK per kelas: SATU akun untuk SETIAP baris kelas yang ada
        // (kelas + jurusan apa pun) — otomatis mengikuti isi tabel kelas,
        // tidak hardcode 3 kelas. Email stabil dari slug nama kelas,
        // mis. "X PPLG" -> mpk-x-pplg@sekolah.test.
        $nis = 1000;
        foreach (ClassRoom::orderBy('id')->get() as $kelas) {
            $nis++;

            $model = User::updateOrCreate(
                ['email' => 'mpk-'.$this->slugKelas($kelas->nama_kelas).'@sekolah.test'],
                [
                    'name' => 'MPK '.$kelas->nama_kelas,
                    'password' => Hash::make('password'),
                    'role' => 'mpk',
                    'kelas_id' => $kelas->id,
                    'email_verified_at' => now(),
                ]
            );

            Siswa::updateOrCreate(
                ['user_id' => $model->id],
                ['kelas_id' => $kelas->id, 'nis' => (string) $nis]
            );
        }

        // MPK tanpa kelas (uji negatif: tidak bisa validasi apa pun).
        $tanpaKelas = User::updateOrCreate(
            ['email' => 'mpk-tanpa-kelas@sekolah.test'],
            [
                'name' => 'MPK Tanpa Kelas',
                'password' => Hash::make('password'),
                'role' => 'mpk',
                'kelas_id' => null,
                'email_verified_at' => now(),
            ]
        );

        Siswa::updateOrCreate(
            ['user_id' => $tanpaKelas->id],
            ['kelas_id' => null, 'nis' => null]
        );
    }

    /**
     * "XII RPL" -> "xii-rpl". Dipakai agar email MPK stabil &
     * bisa ditebak dari nama kelas (idempotent antar seed).
     */
    private function slugKelas(string $nama): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($nama)), '-');
    }
}

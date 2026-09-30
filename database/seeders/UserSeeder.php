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
        // Petakan email MPK -> nama kelas binaannya.
        // Null = MPK tanpa kelas (uji negatif).
        $kelasIdByName = ClassRoom::pluck('id', 'nama_kelas')->all();

        $users = [
            [
                'name' => 'Admin Sekolah',
                'email' => 'admin@sekolah.test',
                'role' => 'admin',
                'kelas_nama' => null,
                'nis' => null,
            ],
            [
                'name' => 'Guru Contoh',
                'email' => 'guru@sekolah.test',
                'role' => 'guru',
                'kelas_nama' => null,
                'nis' => null,
            ],
            // Guru asli sesuai tabel jadwal XII RPL (inisial pengampu di gambar).
            // Mohamad Jamali = guru Bahasa Inggris SEKALIGUS wali kelas XII RPL.
            // Nama lain memakai inisial + mapel (bisa diganti nama asli via Kelola User).
            ['name' => 'Mohamad Jamali', 'email' => 'mj@sekolah.test', 'role' => 'guru', 'kelas_nama' => null, 'nis' => null],
            ['name' => 'Ro (KK RPL)', 'email' => 'ro@sekolah.test', 'role' => 'guru', 'kelas_nama' => null, 'nis' => null],
            ['name' => 'AHU (KIK RPL)', 'email' => 'ahu@sekolah.test', 'role' => 'guru', 'kelas_nama' => null, 'nis' => null],
            ['name' => 'DS (BJW)', 'email' => 'ds@sekolah.test', 'role' => 'guru', 'kelas_nama' => null, 'nis' => null],
            ['name' => 'SDA (PP)', 'email' => 'sda@sekolah.test', 'role' => 'guru', 'kelas_nama' => null, 'nis' => null],
            ['name' => 'WK (KK RPL)', 'email' => 'wk@sekolah.test', 'role' => 'guru', 'kelas_nama' => null, 'nis' => null],
            ['name' => 'ANA (MP RPL)', 'email' => 'ana@sekolah.test', 'role' => 'guru', 'kelas_nama' => null, 'nis' => null],
            ['name' => 'NF (PABP)', 'email' => 'nf@sekolah.test', 'role' => 'guru', 'kelas_nama' => null, 'nis' => null],
            ['name' => 'DN (MTK)', 'email' => 'dn@sekolah.test', 'role' => 'guru', 'kelas_nama' => null, 'nis' => null],
            ['name' => 'HI (BK)', 'email' => 'hi@sekolah.test', 'role' => 'guru', 'kelas_nama' => null, 'nis' => null],
            ['name' => 'DP (BI)', 'email' => 'dp@sekolah.test', 'role' => 'guru', 'kelas_nama' => null, 'nis' => null],
            [
                'name' => 'MPK X PPLG',
                'email' => 'mpk-x-pplg@sekolah.test',
                'role' => 'mpk',
                'kelas_nama' => 'X PPLG',
                'nis' => '1001',
            ],
            [
                'name' => 'MPK XI RPL',
                'email' => 'mpk-xi-rpl@sekolah.test',
                'role' => 'mpk',
                'kelas_nama' => 'XI RPL',
                'nis' => '1002',
            ],
            [
                'name' => 'MPK XII RPL',
                'email' => 'mpk-xii-rpl@sekolah.test',
                'role' => 'mpk',
                'kelas_nama' => 'XII RPL',
                'nis' => '1003',
            ],
            [
                'name' => 'MPK Tanpa Kelas',
                'email' => 'mpk-tanpa-kelas@sekolah.test',
                'role' => 'mpk',
                'kelas_nama' => null,
                'nis' => null,
            ],
        ];

        foreach ($users as $user) {
            $kelasId = $user['kelas_nama'] !== null
                ? ($kelasIdByName[$user['kelas_nama']] ?? null)
                : null;

            $model = User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'role' => $user['role'],
                    'kelas_id' => $kelasId,
                    'email_verified_at' => now(),
                ]
            );

            if ($user['role'] === 'mpk') {
                Siswa::updateOrCreate(
                    ['user_id' => $model->id],
                    ['kelas_id' => $kelasId, 'nis' => $user['nis']]
                );
            }
        }
    }
}

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
     * Buat satu akun untuk masing-masing role: admin, guru, mpk.
     *
     * - Admin & guru hanya butuh baris di tabel users.
     * - MPK butuh satu baris pendamping di tabel siswa
     *   (kelas yang dipegang + NIS).
     *
     * Semua pakai password default: `password`.
     * Idempotent (aman dijalankan berulang) via updateOrCreate.
     */
    public function run(): void
    {
        // 1. Admin
        User::updateOrCreate(
            ['email' => 'admin@sekolah.test'],
            [
                'name' => 'Admin Sekolah',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // 2. Guru
        $gurus = [
            ['name' => 'Guru Satu', 'email' => 'guru1@sekolah.test'],
            ['name' => 'Guru Dua', 'email' => 'guru2@sekolah.test'],
            ['name' => 'Guru Tiga', 'email' => 'guru3@sekolah.test'],
        ];

        foreach ($gurus as $guru) {
            User::updateOrCreate(
                ['email' => $guru['email']],
                [
                    'name' => $guru['name'],
                    'password' => Hash::make('password'),
                    'role' => 'guru',
                    'email_verified_at' => now(),
                ]
            );
        }

        // 3. MPK — satu akun per kelas agar tiap kelas ada perwakilan.
        // Pastikan KelasSeeder sudah jalan duluan.
        $mpks = [
            ['name' => 'MPK X PPLG', 'email' => 'mpk.xpplg@sekolah.test', 'nis' => '1001', 'kelas_nama' => 'X PPLG'],
            ['name' => 'MPK XI RPL', 'email' => 'mpk.xirpl@sekolah.test', 'nis' => '1002', 'kelas_nama' => 'XI RPL'],
            ['name' => 'MPK XII RPL', 'email' => 'mpk.xiirpl@sekolah.test', 'nis' => '1003', 'kelas_nama' => 'XII RPL'],
        ];

        foreach ($mpks as $mpk) {
            $user = User::updateOrCreate(
                ['email' => $mpk['email']],
                [
                    'name' => $mpk['name'],
                    'password' => Hash::make('password'),
                    'role' => 'mpk',
                    'email_verified_at' => now(),
                ]
            );

            $kelas = ClassRoom::where('nama_kelas', $mpk['kelas_nama'])->first();

            Siswa::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'kelas_id' => $kelas?->id,
                    'nis' => $mpk['nis'],
                ]
            );
        }
    }
}

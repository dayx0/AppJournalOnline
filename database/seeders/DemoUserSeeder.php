<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\Concerns\DemoOnlySeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoUserSeeder extends Seeder
{
    use DemoOnlySeeder;

    /**
     * Akun contoh semua role untuk pengembangan lokal.
     *
     * Password diambil dari DEMO_SEED_PASSWORD. Seeder menolak berjalan
     * tanpa password yang memadai dan tidak pernah memakai "password".
     *
     * - Penempatan MPK memakai users.kelas_id (sumber kebenaran).
     * - Tiap MPK juga dapat baris profil di tabel siswa (NIS + cermin
     *   kelas) agar fitur profil MPK ikut terisi contoh datanya.
     */
    public function run(): void
    {
        $this->ensureDemoEnvironment();

        $password = (string) config('seeding.demo_password');

        if (mb_strlen($password) < 12) {
            throw new RuntimeException(
                'Set DEMO_SEED_PASSWORD minimal 12 karakter sebelum menjalankan DemoSeeder.'
            );
        }

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
            ['name' => 'DP (BIN)', 'email' => 'dp@sekolah.test', 'role' => 'guru'],
        ];

        foreach ($users as $user) {
            $this->simpanUser([
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'kelas_id' => null,
            ], $password);
        }

        // MPK per kelas: SATU akun untuk SETIAP baris kelas yang ada
        // (kelas + jurusan apa pun) — otomatis mengikuti isi tabel kelas,
        // tidak hardcode 3 kelas. Email stabil dari slug nama kelas,
        // mis. "X PPLG" -> mpk-x-pplg@sekolah.test.
        $nis = 1000;
        foreach (ClassRoom::orderBy('id')->get() as $kelas) {
            $nis++;

            $model = $this->simpanUser([
                'name' => 'MPK '.$kelas->nama_kelas,
                'email' => 'mpk-'.$this->slugKelas($kelas->nama_kelas).'@sekolah.test',
                'role' => 'mpk',
                'kelas_id' => $kelas->id,
            ], $password);

            Siswa::updateOrCreate(
                ['user_id' => $model->id],
                ['kelas_id' => $kelas->id, 'nis' => (string) $nis]
            );
        }

        // MPK tanpa kelas (uji negatif: tidak bisa validasi apa pun).
        $tanpaKelas = $this->simpanUser([
            'name' => 'MPK Tanpa Kelas',
            'email' => 'mpk-tanpa-kelas@sekolah.test',
            'role' => 'mpk',
            'kelas_id' => null,
        ], $password);

        Siswa::updateOrCreate(
            ['user_id' => $tanpaKelas->id],
            ['kelas_id' => null, 'nis' => null]
        );
    }

    /**
     * @param  array{name: string, email: string, role: string, kelas_id: int|null}  $data
     */
    private function simpanUser(array $data, string $password): User
    {
        $user = User::firstOrNew(['email' => $data['email']]);

        $user->fill([
            'name' => $data['name'],
            'kode_guru' => $data['role'] === 'guru'
                ? User::generateKodeGuru($data['name'])
                : null,
            'role' => $data['role'],
            'kelas_id' => $data['kelas_id'],
            'email_verified_at' => now(),
            'is_active' => true,
            'must_change_password' => false,
        ]);

        // Akun baru selalu memakai password dari env. Akun lama hanya
        // dirotasi jika masih memakai password demo lama "password".
        if (! $user->exists || Hash::check('password', (string) $user->password)) {
            $user->password = Hash::make($password);
        }

        $user->save();

        return $user;
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

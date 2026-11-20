<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminSeeder extends Seeder
{
    /**
     * Membuat satu akun admin awal dari ADMIN_EMAIL dan ADMIN_PASSWORD.
     *
     * Tidak ada password default. Di produksi seeder gagal bila kredensial
     * belum diisi agar deployment tidak berjalan tanpa admin yang aman.
     */
    public function run(): void
    {
        $name = trim((string) config('seeding.admin.name', 'Administrator Sekolah'));
        $email = trim((string) config('seeding.admin.email'));
        $password = (string) config('seeding.admin.password');

        if ($email === '' || $password === '') {
            $message = 'Set ADMIN_EMAIL dan ADMIN_PASSWORD (minimal 12 karakter) sebelum menjalankan seeder.';

            if (app()->environment('production')) {
                throw new RuntimeException($message);
            }

            $this->command?->warn('AdminSeeder dilewati: '.$message);

            return;
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('ADMIN_EMAIL tidak valid.');
        }

        if (mb_strlen($password) < 12) {
            throw new RuntimeException('ADMIN_PASSWORD minimal 12 karakter.');
        }

        $existing = User::where('email', $email)->first();

        if ($existing !== null) {
            if (! $existing->isAdmin()) {
                throw new RuntimeException('Email admin sudah dipakai akun non-admin.');
            }

            $this->command?->info('AdminSeeder: akun admin sudah ada, password tidak diubah.');

            return;
        }

        User::create([
            'name' => $name !== '' ? $name : 'Administrator Sekolah',
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'email_verified_at' => now(),
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $this->command?->info('AdminSeeder: akun admin awal berhasil dibuat.');
    }
}

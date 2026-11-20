<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seeder produksi: data master + akun admin awal.
     *
     * Data demo (akun contoh, jadwal acak, jurnal simulasi) TIDAK dipanggil
     * di sini. Untuk pengembangan lokal gunakan:
     *   php artisan db:seed --class=DemoSeeder
     */
    public function run(): void
    {
        $this->call([
            JurusanSeeder::class,
            KelasSeeder::class,
            MataPelajaranSeeder::class,
            TahunAjaranSeeder::class,
            AdminSeeder::class,
        ]);
    }
}

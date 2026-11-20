<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\DemoOnlySeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    use DemoOnlySeeder, WithoutModelEvents;

    /**
     * Data demo lengkap untuk pengembangan lokal.
     *
     * Jalankan dengan:
     *   php artisan db:seed --class=DemoSeeder
     *
     * Seeder ini sengaja tidak dipanggil oleh DatabaseSeeder agar
     * `php artisan db:seed` tidak pernah membuat akun/jadwal demo.
     */
    public function run(): void
    {
        $this->ensureDemoEnvironment();

        $this->call([
            JurusanSeeder::class,
            KelasSeeder::class,
            MataPelajaranSeeder::class,
            TahunAjaranSeeder::class,
            DemoUserSeeder::class,
            DemoJadwalSeeder::class,
            JurnalCekMpkSeeder::class,
        ]);
    }
}

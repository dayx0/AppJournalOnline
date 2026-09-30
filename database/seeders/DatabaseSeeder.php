<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Urutan penting: kelas+mapel dulu (dibutuhkan jadwal),
        // lalu user (dibutuhkan wali & guru pengampu),
        // lalu jadwal (dibutuhkan slot jurnal).
        $this->call([
            KelasSeeder::class,
            MataPelajaranSeeder::class,
            AdminSeeder::class,
            UserSeeder::class,
            JadwalSeeder::class,
            JurnalCekMpkSeeder::class,
        ]);
    }
}

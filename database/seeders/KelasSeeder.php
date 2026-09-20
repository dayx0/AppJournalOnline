<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KelasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['nama_kelas' => 'X PPLG', 'jurusan' => 'RPL', 'tingkat' => 10],
            ['nama_kelas' => 'XI RPL', 'jurusan' => 'RPL', 'tingkat' => 11],
            ['nama_kelas' => 'XII RPL', 'jurusan' => 'RPL', 'tingkat' => 12],
        ];

        foreach ($data as $item) {
            ClassRoom::firstOrCreate($item);
        }
    }
}

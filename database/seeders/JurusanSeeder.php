<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use Illuminate\Database\Seeder;

class JurusanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Jurusan::MASTER as $row) {
            Jurusan::updateOrCreate(['kode' => $row['kode']], $row);
        }
    }
}

<?php

namespace Database\Seeders\Concerns;

use RuntimeException;

trait DemoOnlySeeder
{
    /**
     * Seeder demo hanya boleh dijalankan di mesin pengembang/test.
     * Ini mencegah jadwal acak dan akun contoh masuk ke produksi.
     */
    protected function ensureDemoEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException(
                'Seeder demo hanya boleh dijalankan pada lingkungan local/testing.'
            );
        }
    }
}

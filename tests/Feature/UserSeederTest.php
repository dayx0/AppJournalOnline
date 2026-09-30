<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_membuat_mpk_untuk_setiap_kelas(): void
    {
        Artisan::call('db:seed');

        $kelas = ClassRoom::orderBy('id')->get();
        $this->assertGreaterThanOrEqual(3, $kelas->count());

        // Satu MPK per kelas + satu MPK tanpa kelas (uji negatif).
        $this->assertSame($kelas->count() + 1, User::where('role', 'mpk')->count());

        foreach ($kelas as $index => $k) {
            $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($k->nama_kelas)), '-');
            $mpk = User::where('email', "mpk-{$slug}@sekolah.test")->first();

            $this->assertNotNull($mpk, "MPK untuk {$k->nama_kelas} tidak ada");
            $this->assertSame($k->id, (int) $mpk->kelas_id);
            $this->assertSame('MPK '.$k->nama_kelas, $mpk->name);
            $this->assertDatabaseHas('siswa', [
                'user_id' => $mpk->id,
                'kelas_id' => $k->id,
                'nis' => (string) (1001 + $index),
            ]);
        }

        // Idempotent: seed kedua tidak menambah akun.
        Artisan::call('db:seed');
        $this->assertSame($kelas->count() + 1, User::where('role', 'mpk')->count());
    }
}

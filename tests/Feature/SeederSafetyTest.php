<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Jadwal;
use App\Models\Journal;
use App\Models\Jurusan;
use App\Models\Subject;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoJadwalSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class SeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_hanya_membuat_data_master_dan_admin(): void
    {
        config([
            'seeding.admin.name' => 'Admin Uji',
            'seeding.admin.email' => 'admin-uji@sekolah.test',
            'seeding.admin.password' => 'PasswordAdminUji123!',
            'seeding.demo_password' => 'PasswordDemoUji123!',
        ]);

        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin-uji@sekolah.test')->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue(Hash::check('PasswordAdminUji123!', $admin->password));
        $this->assertFalse(Hash::check('password', $admin->password));

        // Tidak ada akun/jadwal/jurnal demo pada seeder produksi.
        $this->assertSame(0, User::whereIn('role', ['guru', 'mpk'])->count());
        $this->assertSame(0, Jadwal::count());
        $this->assertSame(0, Journal::count());
        $this->assertGreaterThan(0, ClassRoom::count());
        $this->assertGreaterThan(0, Subject::count());
    }

    public function test_seeder_produksi_tidak_menimpa_kelas_dan_tahun_ajaran_yang_sudah_ada(): void
    {
        config([
            'seeding.admin.name' => 'Admin Uji',
            'seeding.admin.email' => 'admin-uji@sekolah.test',
            'seeding.admin.password' => 'PasswordAdminUji123!',
        ]);

        $tahun = TahunAjaran::create([
            'nama' => '2030/2031',
            'semester_aktif' => 'ganjil',
            'is_aktif' => true,
            'locked_at' => null,
        ]);
        $jurusan = Jurusan::create([
            'kode' => 'UJI',
            'nama' => 'Jurusan Kustom',
            'kelompok' => 'UJI',
        ]);
        $kelas = ClassRoom::create([
            'nama_kelas' => 'X PPLG',
            'jurusan_id' => $jurusan->id,
            'tingkat' => 10,
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($tahun->id, TahunAjaran::aktif()?->id);
        $this->assertDatabaseHas('kelas', [
            'id' => $kelas->id,
            'jurusan_id' => $jurusan->id,
            'tingkat' => 10,
        ]);
    }

    public function test_demo_seeder_memakai_password_dari_env(): void
    {
        config(['seeding.demo_password' => 'PasswordDemoUji123!']);

        $this->seed(DemoSeeder::class);

        $demoAdmin = User::where('email', 'admin@sekolah.test')->first();

        $this->assertNotNull($demoAdmin);
        $this->assertTrue(Hash::check('PasswordDemoUji123!', $demoAdmin->password));
        $this->assertFalse(Hash::check('password', $demoAdmin->password));
        $this->assertSame(ClassRoom::count() + 1, User::where('role', 'mpk')->count());
        $this->assertGreaterThan(0, Jadwal::count());
        $this->assertGreaterThan(0, Journal::count());
    }

    public function test_demo_seeder_ditolak_di_luar_local_dan_testing(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->expectException(RuntimeException::class);

        app(DemoSeeder::class)->run();
    }

    public function test_demo_jadwal_tidak_menghapus_atau_menimpa_jadwal_yang_ada(): void
    {
        $jurusan = Jurusan::firstOrCreate(
            ['kode' => 'UJI'],
            ['nama' => 'Jurusan Uji', 'kelompok' => 'UJI'],
        );
        $kelas = ClassRoom::create([
            'nama_kelas' => 'X UJI',
            'jurusan_id' => $jurusan->id,
            'tingkat' => 10,
        ]);
        $mapelAsli = Subject::create(['nama_mapel' => 'Mapel Asli', 'kode_mapel' => 'ASLI']);
        Subject::create(['nama_mapel' => 'Mapel Demo', 'kode_mapel' => 'DEMO']);
        $guruAsli = User::factory()->create(['role' => 'guru']);
        User::factory()->create(['role' => 'guru']);

        $jadwalAsli = Jadwal::create([
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapelAsli->id,
            'guru_id' => $guruAsli->id,
            'hari' => 'senin',
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:45',
            'semester' => '2026/2027-ganjil',
            'tahun_ajaran' => '2026/2027',
            'aktif' => true,
        ]);

        $this->seed(DemoJadwalSeeder::class);

        $this->assertDatabaseHas('jadwal', [
            'id' => $jadwalAsli->id,
            'mapel_id' => $mapelAsli->id,
            'guru_id' => $guruAsli->id,
            'jam_selesai' => '08:45',
        ]);
        $this->assertSame(20, Jadwal::count());
    }
}

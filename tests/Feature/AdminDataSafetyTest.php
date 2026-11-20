<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Jadwal;
use App\Models\Journal;
use App\Models\Jurusan;
use App\Models\Siswa;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDataSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function kelas(string $nama = 'X UJI'): ClassRoom
    {
        $jurusan = Jurusan::firstOrCreate(
            ['kode' => 'UJI'],
            ['nama' => 'Jurusan Uji', 'kelompok' => 'UJI'],
        );

        return ClassRoom::create([
            'nama_kelas' => $nama,
            'jurusan_id' => $jurusan->id,
            'tingkat' => 10,
        ]);
    }

    private function mapel(string $kode = 'UJI'): Subject
    {
        return Subject::create([
            'nama_mapel' => 'Mapel '.$kode,
            'kode_mapel' => $kode,
        ]);
    }

    private function jadwal(ClassRoom $kelas, Subject $mapel, User $guru): Jadwal
    {
        return Jadwal::create([
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'guru_id' => $guru->id,
            'hari' => 'senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:30',
            'semester' => '2026/2027-ganjil',
            'tahun_ajaran' => '2026/2027',
            'aktif' => true,
        ]);
    }

    private function jurnal(ClassRoom $kelas, Subject $mapel, User $guru, ?User $validator = null): Journal
    {
        return Journal::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:30',
            'materi' => 'Materi uji',
            'kegiatan' => 'Kegiatan uji',
            'status' => $validator === null ? 'pending' : 'divalidasi',
            'validated_by' => $validator?->id,
            'validated_at' => $validator === null ? null : now(),
        ]);
    }

    public function test_user_yang_punya_jurnal_tidak_bisa_dihapus(): void
    {
        $admin = $this->admin();
        $guru = User::factory()->create(['role' => 'guru']);
        $jurnal = $this->jurnal($this->kelas(), $this->mapel(), $guru);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $guru))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $guru->id]);
        $this->assertDatabaseHas('jurnal', ['id' => $jurnal->id]);
    }

    public function test_user_yang_menjadi_wali_kelas_tidak_bisa_dihapus(): void
    {
        $admin = $this->admin();
        $guru = User::factory()->create(['role' => 'guru']);
        $this->kelas()->update(['wali_kelas_id' => $guru->id]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $guru))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $guru->id]);
    }

    public function test_user_yang_punya_jadwal_tidak_bisa_dihapus(): void
    {
        $admin = $this->admin();
        $guru = User::factory()->create(['role' => 'guru']);
        $jadwal = $this->jadwal($this->kelas(), $this->mapel(), $guru);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $guru))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $guru->id]);
        $this->assertDatabaseHas('jadwal', ['id' => $jadwal->id, 'guru_id' => $guru->id]);
    }

    public function test_user_yang_menjadi_validator_tidak_bisa_dihapus(): void
    {
        $admin = $this->admin();
        $guru = User::factory()->create(['role' => 'guru']);
        $mpk = User::factory()->create(['role' => 'mpk']);
        $this->jurnal($this->kelas(), $this->mapel(), $guru, $mpk);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $mpk))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $mpk->id]);
    }

    public function test_user_tanpa_relasi_tetap_bisa_dihapus(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'guru']);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_kelas_yang_punya_jurnal_tidak_bisa_dihapus(): void
    {
        $admin = $this->admin();
        $guru = User::factory()->create(['role' => 'guru']);
        $kelas = $this->kelas();
        $this->jurnal($kelas, $this->mapel(), $guru);

        $this->actingAs($admin)
            ->delete(route('admin.kelas.destroy', $kelas))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('kelas', ['id' => $kelas->id]);
    }

    public function test_kelas_yang_punya_jadwal_tidak_bisa_dihapus(): void
    {
        $admin = $this->admin();
        $guru = User::factory()->create(['role' => 'guru']);
        $kelas = $this->kelas();
        $this->jadwal($kelas, $this->mapel(), $guru);

        $this->actingAs($admin)
            ->delete(route('admin.kelas.destroy', $kelas))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('kelas', ['id' => $kelas->id]);
    }

    public function test_kelas_yang_punya_siswa_tidak_bisa_dihapus(): void
    {
        $admin = $this->admin();
        $kelas = $this->kelas();
        $siswaUser = User::factory()->create(['role' => 'mpk']);
        Siswa::create(['user_id' => $siswaUser->id, 'kelas_id' => $kelas->id, 'nis' => '123']);

        $this->actingAs($admin)
            ->delete(route('admin.kelas.destroy', $kelas))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('kelas', ['id' => $kelas->id]);
    }

    public function test_kelas_yang_ditempati_mpk_tidak_bisa_dihapus(): void
    {
        $admin = $this->admin();
        $kelas = $this->kelas();
        User::factory()->create(['role' => 'mpk', 'kelas_id' => $kelas->id]);

        $this->actingAs($admin)
            ->delete(route('admin.kelas.destroy', $kelas))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('kelas', ['id' => $kelas->id]);
    }

    public function test_kelas_tanpa_relasi_tetap_bisa_dihapus(): void
    {
        $admin = $this->admin();
        $kelas = $this->kelas();

        $this->actingAs($admin)
            ->delete(route('admin.kelas.destroy', $kelas))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('kelas', ['id' => $kelas->id]);
    }

    public function test_mapel_yang_punya_jurnal_tidak_bisa_dihapus(): void
    {
        $admin = $this->admin();
        $guru = User::factory()->create(['role' => 'guru']);
        $mapel = $this->mapel();
        $this->jurnal($this->kelas(), $mapel, $guru);

        $this->actingAs($admin)
            ->delete(route('admin.mapel.destroy', $mapel))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('mata_pelajaran', ['id' => $mapel->id]);
    }

    public function test_mapel_yang_punya_jadwal_tidak_bisa_dihapus(): void
    {
        $admin = $this->admin();
        $guru = User::factory()->create(['role' => 'guru']);
        $mapel = $this->mapel();
        $this->jadwal($this->kelas(), $mapel, $guru);

        $this->actingAs($admin)
            ->delete(route('admin.mapel.destroy', $mapel))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('mata_pelajaran', ['id' => $mapel->id]);
    }

    public function test_mapel_tanpa_relasi_tetap_bisa_dihapus(): void
    {
        $admin = $this->admin();
        $mapel = $this->mapel();

        $this->actingAs($admin)
            ->delete(route('admin.mapel.destroy', $mapel))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('mata_pelajaran', ['id' => $mapel->id]);
    }
}

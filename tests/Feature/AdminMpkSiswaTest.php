<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMpkSiswaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function kelas(string $nama = 'XII IPA 1'): ClassRoom
    {
        return ClassRoom::create(['nama_kelas' => $nama, 'jurusan' => 'IPA', 'tingkat' => 'XII']);
    }

    public function test_admin_membuat_mpk_ikut_membuat_baris_siswa()
    {
        $kelas = $this->kelas();

        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'MPK Satu',
            'email' => 'mpk1@sekolah.id',
            'password' => 'password123',
            'role' => 'mpk',
            'kelas_id' => $kelas->id,
            'nis' => '12345',
        ])->assertRedirect();

        $user = User::where('email', 'mpk1@sekolah.id')->first();
        $this->assertNotNull($user);
        $this->assertDatabaseHas('siswa', [
            'user_id' => $user->id,
            'kelas_id' => $kelas->id,
            'nis' => '12345',
        ]);
    }

    public function test_admin_membuat_mpk_wajib_pilih_kelas()
    {
        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'MPK Tanpa Kelas',
            'email' => 'mpk2@sekolah.id',
            'password' => 'password123',
            'role' => 'mpk',
            'kelas_id' => null,
        ])->assertSessionHasErrors('kelas_id');

        $this->assertDatabaseMissing('users', ['email' => 'mpk2@sekolah.id']);
    }

    public function test_admin_mengubah_kelas_dan_nis_mpk()
    {
        $kelasA = $this->kelas('XII IPA 1');
        $kelasB = $this->kelas('XII IPS 1');
        $admin = $this->admin();
        $mpk = User::factory()->create(['role' => 'mpk']);
        Siswa::create(['user_id' => $mpk->id, 'kelas_id' => $kelasA->id, 'nis' => '111']);

        $this->actingAs($admin)->put(route('admin.users.update', $mpk), [
            'name' => $mpk->name,
            'email' => $mpk->email,
            'role' => 'mpk',
            'kelas_id' => $kelasB->id,
            'nis' => '222',
        ])->assertRedirect();

        $this->assertDatabaseHas('siswa', [
            'user_id' => $mpk->id,
            'kelas_id' => $kelasB->id,
            'nis' => '222',
        ]);
        $this->assertSame(1, Siswa::where('user_id', $mpk->id)->count());
    }

    public function test_mpk_berubah_jadi_guru_menghapus_baris_siswa()
    {
        $kelas = $this->kelas();
        $admin = $this->admin();
        $mpk = User::factory()->create(['role' => 'mpk']);
        Siswa::create(['user_id' => $mpk->id, 'kelas_id' => $kelas->id]);

        $this->actingAs($admin)->put(route('admin.users.update', $mpk), [
            'name' => $mpk->name,
            'email' => $mpk->email,
            'role' => 'guru',
        ])->assertRedirect();

        $this->assertDatabaseMissing('siswa', ['user_id' => $mpk->id]);
    }

    public function test_nis_tidak_boleh_kembar()
    {
        $kelas = $this->kelas();
        $mpk = User::factory()->create(['role' => 'mpk']);
        Siswa::create(['user_id' => $mpk->id, 'kelas_id' => $kelas->id, 'nis' => '999']);

        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'MPK Kembar',
            'email' => 'mpk3@sekolah.id',
            'password' => 'password123',
            'role' => 'mpk',
            'kelas_id' => $kelas->id,
            'nis' => '999',
        ])->assertSessionHasErrors('nis');
    }
}

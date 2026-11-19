<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Jadwal;
use App\Models\Journal;
use App\Models\Jurusan;
use App\Models\Subject;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\TahunAjaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TahunAjaranTest extends TestCase
{
    use RefreshDatabase;

    private function makeJurusan(): Jurusan
    {
        return Jurusan::firstOrCreate(
            ['kode' => 'UMUM'],
            ['nama' => 'Umum', 'kelompok' => 'UMUM']
        );
    }

    private function makePaket(string $tahun = '2026/2027', string $semester = 'ganjil'): array
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $kelas = ClassRoom::create([
            'nama_kelas' => 'X-A', 'jurusan_id' => $this->makeJurusan()->id, 'tingkat' => 10,
        ]);
        $mapel = Subject::create(['nama_mapel' => 'Matematika', 'kode_mapel' => 'MTK']);
        $mpk = User::factory()->create(['role' => 'mpk', 'kelas_id' => $kelas->id]);
        $journal = Journal::create([
            'guru_id' => $guru->id, 'kelas_id' => $kelas->id, 'mapel_id' => $mapel->id,
            'tanggal' => '2026-09-10', 'tahun_ajaran' => $tahun, 'semester' => $semester,
            'jam_mulai' => '07:00', 'jam_selesai' => '08:30',
            'materi' => 'Aljabar', 'kegiatan' => 'Ceramah', 'status' => 'pending',
        ]);

        return compact('guru', 'kelas', 'mapel', 'mpk', 'journal');
    }

    private function bukaAktif(string $nama = '2026/2027', string $semester = 'ganjil'): TahunAjaran
    {
        return TahunAjaran::create(['nama' => $nama, 'semester_aktif' => $semester, 'is_aktif' => true]);
    }

    public function test_akun_nonaktif_ditolak_login_dan_ditendang_dari_route(): void
    {
        $user = User::factory()->create(['role' => 'guru', 'is_active' => false]);

        // Coba login -> ditolak dengan error.
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        // Sesi yang masih hidup (mis. dinonaktifkan saat login) -> kick.
        $aktif = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $this->actingAs($aktif);
        $aktif->update(['is_active' => false]);
        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_flag_wajib_ganti_password_memaksa_ke_halaman_security(): void
    {
        $mpk = User::factory()->create(['role' => 'mpk', 'must_change_password' => true]);

        $this->actingAs($mpk)->get('/dashboard')
            ->assertRedirect(route('security.edit'));

        // Setelah ganti password, flag bersih dan bebas akses.
        $this->actingAs($mpk->fresh())->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'password-baru-aman',
            'password_confirmation' => 'password-baru-aman',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($mpk->fresh()->must_change_password);
        // /dashboard mengarahkan MPK ke dasbor MPK (bukan 200 langsung).
        $this->actingAs($mpk->fresh())->get('/dashboard')
            ->assertRedirect(route('mpk.dashboard'));
    }

    public function test_jurnal_arsip_tidak_bisa_diubah_atau_divalidasi(): void
    {
        $this->bukaAktif('2026/2027', 'ganjil');
        $p = $this->makePaket('2025/2026', 'genap');

        // Guru: update ditolak.
        $this->actingAs($p['guru'])->put(route('jurnal.update', $p['journal']), [
            'materi' => 'Ubah', 'kegiatan' => 'Ubah',
        ])->assertStatus(422);

        // MPK: validasi ditolak.
        $this->actingAs($p['mpk'])->post(route('mpk.jurnal.validate', $p['journal']))
            ->assertStatus(422);

        // Jurnal tahun berjalan tetap bisa divalidasi.
        $aktif = $this->makePaket('2026/2027', 'ganjil');
        $this->actingAs($aktif['mpk'])->post(route('mpk.jurnal.validate', $aktif['journal']), [])
            ->assertRedirect();
        $this->assertSame('divalidasi', $aktif['journal']->fresh()->status);
    }

    public function test_buka_tahun_baru_mengunci_arsip_dan_menonaktifkan_jadwal(): void
    {
        $this->bukaAktif('2025/2026', 'genap');
        $p = $this->makePaket('2025/2026', 'genap');
        $jadwal = Jadwal::create([
            'kelas_id' => $p['kelas']->id, 'mapel_id' => $p['mapel']->id, 'guru_id' => $p['guru']->id,
            'hari' => 'senin', 'jam_mulai' => '07:00', 'jam_selesai' => '08:30',
            'semester' => 'genap', 'tahun_ajaran' => '2025/2026', 'aktif' => true,
        ]);

        $hasil = TahunAjaranService::bukaBaru('2026/2027', 'ganjil');

        $this->assertSame('2026/2027', $hasil['baru']->nama);
        $this->assertSame(1, $hasil['jurnal_terkunci']);
        $this->assertSame(1, $hasil['jadwal_dinonaktifkan']);
        $this->assertFalse($jadwal->fresh()->aktif);
        $this->assertTrue($p['journal']->fresh()->isTerkunci());
        // Akun & kelas TIDAK tersentuh (Model B).
        $this->assertSame($p['kelas']->id, (int) $p['mpk']->fresh()->kelas_id);
        $this->assertTrue((bool) $p['mpk']->fresh()->is_active);
    }

    public function test_reset_password_mpk_mengacak_dan_menandai_flag(): void
    {
        $p = $this->makePaket();

        $kredensial = TahunAjaranService::resetPasswordMpk();

        $this->assertNotEmpty($kredensial);
        $baris = collect($kredensial)->firstWhere('id', $p['mpk']->id);
        $this->assertNotNull($baris);
        $this->assertTrue(Hash::check($baris['password'], $p['mpk']->fresh()->password));
        $this->assertTrue((bool) $p['mpk']->fresh()->must_change_password);
    }
}

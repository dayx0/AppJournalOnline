<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Journal;
use App\Models\Siswa;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RekapHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function makeMpk(ClassRoom $kelas, ?string $nis = null): User
    {
        $mpk = User::factory()->create(['role' => 'mpk']);
        Siswa::create(['user_id' => $mpk->id, 'kelas_id' => $kelas->id, 'nis' => $nis]);

        return $mpk;
    }

    private function master(): array
    {
        $kelas = ClassRoom::create(['nama_kelas' => 'XII IPA 1', 'jurusan' => 'IPA', 'tingkat' => 'XII']);
        $mapel = Subject::create(['nama_mapel' => 'Matematika', 'kode_mapel' => 'MTK']);

        return [$kelas, $mapel];
    }

    private function journal(User $guru, string $tanggal = '2026-09-10', string $status = 'pending', ?ClassRoom $kelas = null, ?Subject $mapel = null): Journal
    {
        $kelas ??= ClassRoom::create(['nama_kelas' => 'XII IPA '.fake()->unique()->numberBetween(1, 99), 'jurusan' => 'IPA', 'tingkat' => 'XII']);
        $mapel ??= Subject::create(['nama_mapel' => 'Matematika '.fake()->unique()->word(), 'kode_mapel' => 'MTK'.fake()->unique()->numberBetween(100, 999)]);

        return Journal::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'tanggal' => $tanggal,
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
            'materi' => 'Aljabar',
            'kegiatan' => 'Ceramah',
            'status' => $status,
        ]);
    }

    public function test_riwayat_tercatat_saat_dibuat_divalidasi_dan_reset()
    {
        $guru = User::factory()->create(['role' => 'guru']);
        [$kelas, $mapel] = $this->master();
        $mpk = $this->makeMpk($kelas);

        // Dibuat via HTTP agar trigger controller ikut jalan
        $this->actingAs($guru)->post(route('jurnal.store'), [
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'tanggal' => '2026-09-10',
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
            'materi' => 'Aljabar',
            'kegiatan' => 'Ceramah',
        ])->assertRedirect(route('jurnal.index'));

        $journal = Journal::first();
        $this->assertDatabaseHas('journal_histories', [
            'journal_id' => $journal->id,
            'aksi' => 'dibuat',
            'ke_status' => 'pending',
        ]);

        // Divalidasi MPK
        $this->actingAs($mpk)
            ->post(route('mpk.jurnal.validate', $journal), ['validation_note' => 'Bagus'])
            ->assertRedirect();
        $this->assertDatabaseHas('journal_histories', [
            'journal_id' => $journal->id,
            'aksi' => 'divalidasi',
            'dari_status' => 'pending',
            'ke_status' => 'divalidasi',
        ]);

        // Guru ubah -> reset ke pending + riwayat reset
        $this->actingAs($guru)->put(route('jurnal.update', $journal), [
            'kelas_id' => $journal->kelas_id,
            'mapel_id' => $journal->mapel_id,
            'tanggal' => '2026-09-10',
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
            'materi' => 'Aljabar revisi',
            'kegiatan' => 'Ceramah',
        ])->assertRedirect(route('jurnal.index'));
        $this->assertDatabaseHas('journal_histories', [
            'journal_id' => $journal->id,
            'aksi' => 'reset_pending',
            'dari_status' => 'divalidasi',
            'ke_status' => 'pending',
        ]);

        // Riwayat tampil di kedua halaman detail
        $this->actingAs($guru)->get(route('jurnal.show', $journal))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('jurnal/show')->has('histories', 3));
        $this->actingAs($mpk)->get(route('mpk.jurnal.show', $journal))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('mpk/jurnal/show')->has('histories', 3));
    }

    public function test_rekap_filter_ringkasan_dan_export_csv()
    {
        $guru = User::factory()->create(['role' => 'guru']);
        [$kelas, $mapel] = $this->master();
        $mpk = $this->makeMpk($kelas);
        $this->journal($guru, '2026-09-05', 'divalidasi', $kelas, $mapel);
        $this->journal($guru, '2026-09-12', 'pending', $kelas, $mapel);

        // Filter rentang tanggal: hanya 1 yang masuk
        $this->actingAs($mpk)->get(route('mpk.rekap.index', ['dari' => '2026-09-10', 'sampai' => '2026-09-15']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mpk/rekap')
                ->where('summary.total', 1)
                ->where('summary.pending', 1)
                ->has('jurnals.data', 1));

        // Filter status tanpa batas tanggal: 1 divalidasi
        $this->actingAs($mpk)->get(route('mpk.rekap.index', ['status' => 'divalidasi']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.divalidasi', 1)
                ->has('jurnals.data', 1));

        // Export CSV mengikuti filter + bisa dibuka Excel
        $response = $this->actingAs($mpk)->get(route('mpk.rekap.export', ['status' => 'pending']));
        $response->assertOk();
        $this->assertStringContainsString('.csv', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Tanggal', (string) $response->streamedContent());
        $this->assertStringContainsString('Aljabar', (string) $response->streamedContent());
    }

    public function test_guru_tidak_bisa_buka_rekap_mpk()
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->get(route('mpk.rekap.index'))->assertForbidden();
        $this->actingAs($guru)->get(route('mpk.rekap.export'))->assertForbidden();
    }

    public function test_mpk_hanya_melihat_kelasnya_sendiri()
    {
        $guru = User::factory()->create(['role' => 'guru']);
        [$kelasA, $mapel] = $this->master();
        $kelasB = ClassRoom::create(['nama_kelas' => 'XII IPS 1', 'jurusan' => 'IPS', 'tingkat' => 'XII']);
        $mpkA = $this->makeMpk($kelasA);

        $jurnalA = $this->journal($guru, '2026-09-10', 'pending', $kelasA, $mapel);
        $jurnalB = $this->journal($guru, '2026-09-10', 'pending', $kelasB, $mapel);

        // Index hanya berisi jurnal kelas sendiri
        $this->actingAs($mpkA)->get(route('mpk.jurnal.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mpk/jurnal/index')
                ->has('jurnals.data', 1)
                ->where('jurnals.data.0.id', $jurnalA->id));

        // Detail & validasi kelas lain ditolak
        $this->actingAs($mpkA)->get(route('mpk.jurnal.show', $jurnalB))->assertForbidden();
        $this->actingAs($mpkA)->post(route('mpk.jurnal.validate', $jurnalB))->assertForbidden();
        $this->actingAs($mpkA)->post(route('mpk.jurnal.revisi', $jurnalB), ['validation_note' => 'Salah kelas'])->assertForbidden();

        // Dashboard & rekap juga hanya kelas sendiri
        $this->actingAs($mpkA)->get(route('mpk.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total', 1)
                ->where('stats.pending', 1));

        $this->actingAs($mpkA)->get(route('mpk.rekap.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.total', 1)
                ->has('jurnals.data', 1));
    }

    public function test_mpk_tanpa_kelas_tidak_melihat_apa_pun()
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $mpk = User::factory()->create(['role' => 'mpk']);
        $journal = $this->journal($guru);

        $this->actingAs($mpk)->get(route('mpk.jurnal.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('jurnals.data', 0));

        $this->actingAs($mpk)->get(route('mpk.jurnal.show', $journal))->assertForbidden();
        $this->actingAs($mpk)->get(route('mpk.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('stats.total', 0));
    }
}

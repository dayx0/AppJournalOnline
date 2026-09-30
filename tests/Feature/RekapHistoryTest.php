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
        $mpk = User::factory()->create(['role' => 'mpk', 'kelas_id' => $kelas->id]);
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

    public function test_riwayat_tercatat_saat_diisi_divalidasi_dan_final()
    {
        $guru = User::factory()->create(['role' => 'guru']);
        [$kelas, $mapel] = $this->master();
        $mpk = $this->makeMpk($kelas);

        // Slot menunggu (seolah dari scheduler), diisi guru via HTTP
        // agar trigger controller ikut jalan: menunggu -> pending.
        // Tanggal = hari ini (guard: guru hanya boleh isi slot hari ini).
        $slot = Journal::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
            'status' => 'menunggu',
        ]);

        $this->actingAs($guru)->put(route('jurnal.update', $slot), [
            'materi' => 'Aljabar',
            'kegiatan' => 'Ceramah',
        ])->assertRedirect(route('jurnal.index'));

        $journal = $slot->fresh();
        $this->assertSame('pending', $journal->status);
        // MPK harus ditempati di kelas jurnal agar boleh memvalidasi.
        $mpk->update(['kelas_id' => $journal->kelas_id]);

        $this->assertDatabaseHas('journal_histories', [
            'journal_id' => $journal->id,
            'aksi' => 'diisi',
            'dari_status' => 'menunggu',
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

        // Jurnal final tidak bisa diubah lagi (dulu: reset ke pending).
        $this->actingAs($guru)->put(route('jurnal.update', $journal), [
            'materi' => 'Aljabar revisi',
            'kegiatan' => 'Ceramah',
        ])->assertStatus(422);
        $this->assertSame('divalidasi', $journal->fresh()->status);

        // Riwayat tampil di kedua halaman detail (2 entri: diisi + divalidasi)
        $this->actingAs($guru)->get(route('jurnal.show', $journal))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('jurnal/show')->has('histories', 2));
        $this->actingAs($mpk)->get(route('mpk.jurnal.show', $journal))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('mpk/jurnal/show')->has('histories', 2));
    }

    public function test_rekap_filter_ringkasan_dan_export_csv()
    {
        $guru = User::factory()->create(['role' => 'guru']);
        [$kelas, $mapel] = $this->master();
        // MPK ditempati di kelas yang sama dengan kedua jurnal uji.
        $mpk = $this->makeMpk($kelas);

        $makeJournal = fn (string $tanggal, string $status) => Journal::create([
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

        $makeJournal('2026-09-05', 'divalidasi');
        $makeJournal('2026-09-12', 'pending');

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

        $jurnalA = $this->journal($guru, now()->toDateString(), 'pending', $kelasA, $mapel);
        $jurnalB = $this->journal($guru, now()->toDateString(), 'pending', $kelasB, $mapel);

        // Index hanya berisi jurnal kelas sendiri (default tanggal hari ini)
        $this->actingAs($mpkA)->get(route('mpk.jurnal.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mpk/jurnal/index')
                ->has('jurnals.data', 1)
                ->where('jurnals.data.0.id', $jurnalA->id));

        // Detail & validasi kelas lain ditolak (revisi sudah dihapus,
        // diganti tolak pada alur validasi baru)
        $this->actingAs($mpkA)->get(route('mpk.jurnal.show', $jurnalB))->assertForbidden();
        $this->actingAs($mpkA)->post(route('mpk.jurnal.validate', $jurnalB))->assertForbidden();
        $this->actingAs($mpkA)->post(route('mpk.jurnal.tolak', $jurnalB), ['validation_note' => 'Salah kelas'])->assertForbidden();

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

<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Journal;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MpkKelasScopeTest extends TestCase
{
    use RefreshDatabase;

    private function makeJournal(User $guru, ClassRoom $kelas, Subject $mapel, string $materi = 'Aljabar'): Journal
    {
        return Journal::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
            'materi' => $materi,
            'kegiatan' => 'Ceramah',
            'status' => 'pending',
        ]);
    }

    public function test_mpk_hanya_melihat_jurnal_kelasnya(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $kelasA = ClassRoom::create(['nama_kelas' => 'X-A', 'jurusan' => 'Umum', 'tingkat' => 10]);
        $kelasB = ClassRoom::create(['nama_kelas' => 'X-B', 'jurusan' => 'Umum', 'tingkat' => 10]);
        $mapel = Subject::create(['nama_mapel' => 'Matematika', 'kode_mapel' => 'MTK']);
        $mpkA = User::factory()->create(['role' => 'mpk', 'kelas_id' => $kelasA->id]);

        $this->makeJournal($guru, $kelasA, $mapel, 'Materi A');
        $this->makeJournal($guru, $kelasB, $mapel, 'Materi B');

        $this->actingAs($mpkA)->get(route('mpk.jurnal.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mpk/jurnal/index')
                ->has('jurnals.data', 1)
                ->where('jurnals.data.0.materi', 'Materi A'));
    }

    public function test_mpk_tidak_bisa_validasi_kelas_lain(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $kelasA = ClassRoom::create(['nama_kelas' => 'X-A', 'jurusan' => 'Umum', 'tingkat' => 10]);
        $kelasB = ClassRoom::create(['nama_kelas' => 'X-B', 'jurusan' => 'Umum', 'tingkat' => 10]);
        $mapel = Subject::create(['nama_mapel' => 'Matematika', 'kode_mapel' => 'MTK']);
        $mpkA = User::factory()->create(['role' => 'mpk', 'kelas_id' => $kelasA->id]);

        $journalB = $this->makeJournal($guru, $kelasB, $mapel, 'Materi B');

        $this->actingAs($mpkA)->get(route('mpk.jurnal.show', $journalB))->assertForbidden();
        $this->actingAs($mpkA)->post(route('mpk.jurnal.validate', $journalB))->assertForbidden();
        $this->actingAs($mpkA)->post(route('mpk.jurnal.tolak', $journalB), ['validation_note' => 'Salah kelas'])->assertForbidden();
        $this->actingAs($mpkA)->post(route('mpk.jurnal.tandai-kosong', $journalB))->assertForbidden();
        $this->assertSame('pending', $journalB->fresh()->status);
    }

    public function test_mpk_tanpa_kelas_tidak_melihat_apapun(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $kelas = ClassRoom::create(['nama_kelas' => 'X-A', 'jurusan' => 'Umum', 'tingkat' => 10]);
        $mapel = Subject::create(['nama_mapel' => 'Matematika', 'kode_mapel' => 'MTK']);
        $mpk = User::factory()->create(['role' => 'mpk', 'kelas_id' => null]);

        $journal = $this->makeJournal($guru, $kelas, $mapel);

        $this->actingAs($mpk)->get(route('mpk.jurnal.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('jurnals.data', 0));
        $this->actingAs($mpk)->get(route('mpk.jurnal.show', $journal))->assertForbidden();
        $this->actingAs($mpk)->post(route('mpk.jurnal.validate', $journal))->assertForbidden();
    }

    public function test_notifikasi_hanya_untuk_mpk_kelas_bersangkutan(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $kelasA = ClassRoom::create(['nama_kelas' => 'X-A', 'jurusan' => 'Umum', 'tingkat' => 10]);
        $kelasB = ClassRoom::create(['nama_kelas' => 'X-B', 'jurusan' => 'Umum', 'tingkat' => 10]);
        $mapel = Subject::create(['nama_mapel' => 'Matematika', 'kode_mapel' => 'MTK']);
        $mpkA = User::factory()->create(['role' => 'mpk', 'kelas_id' => $kelasA->id]);
        $mpkB = User::factory()->create(['role' => 'mpk', 'kelas_id' => $kelasB->id]);

        // Alur baru: slot menunggu dibuat (oleh scheduler), guru mengisinya
        // via PUT jurnal.update -> status pending + notifikasi ke MPK sekelas.
        $slot = Journal::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelasA->id,
            'mapel_id' => $mapel->id,
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
            'status' => 'menunggu',
        ]);

        $this->actingAs($guru)->put(route('jurnal.update', $slot), [
            'materi' => 'Materi A',
            'kegiatan' => 'Ceramah',
        ])->assertRedirect(route('jurnal.index'));

        $journal = $slot->fresh();
        $this->assertSame('pending', $journal->status);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $mpkA->id,
            'key' => "jurnal.{$journal->id}.pending",
        ]);
        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $mpkB->id,
            'key' => "jurnal.{$journal->id}.pending",
        ]);
    }
}

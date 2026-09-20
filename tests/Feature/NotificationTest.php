<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Journal;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\JournalNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeMaster(): array
    {
        $kelas = ClassRoom::create(['nama_kelas' => 'XII IPA 1', 'jurusan' => 'IPA', 'tingkat' => 'XII']);
        $mapel = Subject::create(['nama_mapel' => 'Matematika', 'kode_mapel' => 'MTK']);

        return [$kelas, $mapel];
    }

    private function makeJournal(User $guru): Journal
    {
        [$kelas, $mapel] = $this->makeMaster();

        return Journal::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
            'materi' => 'Aljabar',
            'kegiatan' => 'Ceramah dan latihan',
        ]);
    }

    public function test_mpk_mendapat_antrean_unread_saat_guru_membuat_jurnal()
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $mpk = User::factory()->create(['role' => 'mpk']);
        [$kelas, $mapel] = $this->makeMaster();

        $this->actingAs($guru)->post(route('jurnal.store'), [
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
            'materi' => 'Aljabar',
            'kegiatan' => 'Ceramah',
        ])->assertRedirect(route('jurnal.index'));

        $journal = Journal::first();
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $mpk->id,
            'key' => "jurnal.{$journal->id}.pending",
            'kind' => 'pending',
            'read_at' => null,
        ]);
    }

    public function test_guru_mendapat_unread_dan_antrean_mpk_bersih_saat_divalidasi()
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $mpk = User::factory()->create(['role' => 'mpk']);
        $journal = $this->makeJournal($guru);

        // Simulasi antrean pending yang dibuat saat jurnal dibuat
        JournalNotificationService::notifyMpkNewJournal($journal);

        $this->actingAs($mpk)
            ->post(route('mpk.jurnal.validate', $journal), ['validation_note' => 'Bagus'])
            ->assertRedirect();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $guru->id,
            'key' => "jurnal.{$journal->id}.divalidasi",
            'read_at' => null,
        ]);
        $this->assertDatabaseMissing('user_notifications', [
            'key' => "jurnal.{$journal->id}.pending",
        ]);
    }

    public function test_user_dapat_menandai_notifikasi_dibaca()
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $mpk = User::factory()->create(['role' => 'mpk']);
        $journal = $this->makeJournal($guru);
        JournalNotificationService::notifyMpkNewJournal($journal);

        $key = "jurnal.{$journal->id}.pending";

        $this->actingAs($mpk)
            ->post(route('notifikasi.read'), ['keys' => [$key]])
            ->assertRedirect();

        $this->assertNotNull(UserNotification::forUser($mpk->id)->where('key', $key)->first()->read_at);

        $this->actingAs($mpk)
            ->post(route('notifikasi.read-all'))
            ->assertRedirect();

        $this->assertSame(0, UserNotification::forUser($mpk->id)->unread()->count());
    }

    public function test_membuka_detail_jurnal_menandai_baca()
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $mpk = User::factory()->create(['role' => 'mpk']);
        $journal = $this->makeJournal($guru);
        JournalNotificationService::notifyMpkNewJournal($journal);

        $this->actingAs($mpk)->get(route('mpk.jurnal.show', $journal))->assertOk();

        $this->assertSame(0, UserNotification::forUser($mpk->id)->unread()->count());
    }
}

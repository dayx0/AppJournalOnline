<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Jadwal;
use App\Models\Journal;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class JadwalTest extends TestCase
{
    use RefreshDatabase;

    private function master(string $namaKelas = 'X PPLG'): array
    {
        $kelas = ClassRoom::create(['nama_kelas' => $namaKelas, 'jurusan' => 'RPL', 'tingkat' => 10]);
        $mapel = Subject::create(['nama_mapel' => 'Pemrograman Web', 'kode_mapel' => 'PWEB']);
        $guru = User::factory()->create(['role' => 'guru']);

        return [$kelas, $mapel, $guru];
    }

    private function template(ClassRoom $kelas, Subject $mapel, User $guru, string $hari = 'senin'): Jadwal
    {
        return Jadwal::create([
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'guru_id' => $guru->id,
            'hari' => $hari,
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:30',
            'semester' => '2026/2027-ganjil',
            'aktif' => true,
        ]);
    }

    public function test_scheduler_membuat_slot_dan_idempotent(): void
    {
        [$kelas, $mapel, $guru] = $this->master();
        $this->template($kelas, $mapel, $guru, 'senin');

        // 2026-09-28 adalah hari Senin.
        Artisan::call('jurnal:generate-slot', ['--tanggal' => '2026-09-28']);
        $this->assertSame(1, Journal::count());

        $slot = Journal::first();
        $this->assertSame('menunggu', $slot->status);
        $this->assertSame($guru->id, $slot->guru_id);
        $this->assertNull($slot->materi);

        // Dijalankan kedua kali: tidak dobel (unique jadwal_id + tanggal).
        Artisan::call('jurnal:generate-slot', ['--tanggal' => '2026-09-28']);
        $this->assertSame(1, Journal::count());
    }

    public function test_mode_jendela_menerbitkan_h_min_1_sampai_h_plus_7(): void
    {
        // Kunci "hari ini" ke Senin yang diketahui pasti agar deterministik.
        Carbon::setTestNow('2026-09-28');

        try {
            [$kelas, $mapel, $guru] = $this->master();
            $this->template($kelas, $mapel, $guru, 'senin');

            Artisan::call('jurnal:generate-slot');

            // Jendela Min 27 Sep .. Sen 5 Okt, minus Minggu (27 Sep & 4 Okt):
            // template senin cocok 28 Sep + 5 Okt = 2 slot.
            $this->assertSame(2, Journal::count());
            $this->assertSame(
                ['2026-09-28', '2026-10-05'],
                Journal::orderBy('tanggal')->pluck('tanggal')->all()
            );

            // Idempotent: jalan kedua tidak nambah.
            Artisan::call('jurnal:generate-slot');
            $this->assertSame(2, Journal::count());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_wali_mengelola_kelasnya_tapi_ditolak_di_kelas_lain(): void
    {
        [$kelasA, $mapel, $guruA] = $this->master('X-A');
        $kelasB = ClassRoom::create(['nama_kelas' => 'X-B', 'jurusan' => 'RPL', 'tingkat' => 10]);
        $kelasA->update(['wali_kelas_id' => $guruA->id]);

        // Wali boleh membuat jadwal di kelasnya.
        $this->actingAs($guruA)->post(route('jadwal.store'), [
            'kelas_id' => $kelasA->id,
            'mapel_id' => $mapel->id,
            'guru_id' => $guruA->id,
            'hari' => 'senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:30',
            'semester' => '2026/2027-ganjil',
        ])->assertRedirect();
        $this->assertSame(1, Jadwal::count());

        // Wali DITOLAK membuat jadwal di kelas lain (policy, 403).
        $this->actingAs($guruA)->post(route('jadwal.store'), [
            'kelas_id' => $kelasB->id,
            'mapel_id' => $mapel->id,
            'guru_id' => $guruA->id,
            'hari' => 'senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:30',
            'semester' => '2026/2027-ganjil',
        ])->assertForbidden();
        $this->assertSame(1, Jadwal::count());

        // Slot bentrok di kelas & hari yang sama: validasi menolak.
        // Catatan: POST browser biasa (bukan XHR) gagal validasi = 302
        // redirect + error di session, bukan 422 (422 hanya untuk JSON/XHR).
        $this->actingAs($guruA)->post(route('jadwal.store'), [
            'kelas_id' => $kelasA->id,
            'mapel_id' => $mapel->id,
            'guru_id' => $guruA->id,
            'hari' => 'senin',
            'jam_mulai' => '09:00',
            'jam_selesai' => '10:00',
            'semester' => '2026/2027-ganjil',
        ])->assertRedirect()->assertSessionHasErrors('jam_mulai');
        $this->assertSame(1, Jadwal::count());
    }

    public function test_mpk_tolak_dan_tandai_kosong(): void
    {
        [$kelas, $mapel, $guru] = $this->master();
        $mpk = User::factory()->create(['role' => 'mpk', 'kelas_id' => $kelas->id]);

        $terisi = Journal::create([
            'guru_id' => $guru->id, 'kelas_id' => $kelas->id, 'mapel_id' => $mapel->id,
            'tanggal' => now()->toDateString(), 'jam_mulai' => '08:00', 'jam_selesai' => '09:30',
            'materi' => 'HTML', 'kegiatan' => 'Praktik', 'status' => 'pending',
        ]);

        // Tolak tanpa catatan: 302 redirect kembali DENGAN session error
        // (bukan 422 pada POST Inertia biasa? validasi gagal = redirect back).
        $this->actingAs($mpk)->post(route('mpk.jurnal.tolak', $terisi), [])
            ->assertSessionHasErrors('validation_note');

        $this->actingAs($mpk)->post(route('mpk.jurnal.tolak', $terisi), [
            'validation_note' => 'Guru tidak hadir di kelas',
        ])->assertRedirect();
        $this->assertSame('ditolak', $terisi->fresh()->status);

        $kosong = Journal::create([
            'guru_id' => $guru->id, 'kelas_id' => $kelas->id, 'mapel_id' => $mapel->id,
            'tanggal' => now()->toDateString(), 'jam_mulai' => '10:00', 'jam_selesai' => '11:30',
            'status' => 'menunggu',
        ]);

        $this->actingAs($mpk)->post(route('mpk.jurnal.tandai-kosong', $kosong))
            ->assertRedirect();
        $this->assertSame('jam_kosong', $kosong->fresh()->status);

        // Jurnal final tidak bisa ditolak lagi.
        $this->actingAs($mpk)->post(route('mpk.jurnal.tolak', $kosong), [
            'validation_note' => 'x',
        ])->assertStatus(422);
    }

    public function test_slot_izin_tidak_bisa_ditandai_kosong(): void
    {
        [$kelas, $mapel, $guru] = $this->master();
        $mpk = User::factory()->create(['role' => 'mpk', 'kelas_id' => $kelas->id]);

        $slot = Journal::create([
            'guru_id' => $guru->id, 'kelas_id' => $kelas->id, 'mapel_id' => $mapel->id,
            'tanggal' => now()->toDateString(), 'jam_mulai' => '08:00', 'jam_selesai' => '09:30',
            'status' => 'menunggu',
        ]);

        // Guru menandai izin dengan alasan resmi.
        $this->actingAs($guru)->post(route('jurnal.izin', $slot), [
            'catatan' => 'Tugas dinas ke dinas pendidikan',
        ])->assertRedirect();
        $this->assertSame('izin', $slot->fresh()->status);

        // MPK tidak boleh menandainya jam kosong.
        $this->actingAs($mpk)->post(route('mpk.jurnal.tandai-kosong', $slot))
            ->assertStatus(422);
        $this->assertSame('izin', $slot->fresh()->status);
    }

    public function test_guru_susulan_mengubah_jam_kosong_jadi_terlambat(): void
    {
        [$kelas, $mapel, $guru] = $this->master();
        $mpk = User::factory()->create(['role' => 'mpk', 'kelas_id' => $kelas->id]);

        $slot = Journal::create([
            'guru_id' => $guru->id, 'kelas_id' => $kelas->id, 'mapel_id' => $mapel->id,
            'tanggal' => now()->toDateString(), 'jam_mulai' => '08:00', 'jam_selesai' => '09:30',
            'status' => 'menunggu',
        ]);

        $this->actingAs($mpk)->post(route('mpk.jurnal.tandai-kosong', $slot))->assertRedirect();

        // Guru datang terlambat dan mengisi susulan.
        $this->actingAs($guru)->put(route('jurnal.update', $slot), [
            'materi' => 'HTML susulan',
            'kegiatan' => 'Praktik',
        ])->assertRedirect(route('jurnal.index'));

        $this->assertSame('terlambat', $slot->fresh()->status);
        $this->assertDatabaseHas('journal_histories', [
            'journal_id' => $slot->id,
            'aksi' => 'terlambat',
            'dari_status' => 'jam_kosong',
            'ke_status' => 'terlambat',
        ]);
    }

    public function test_grid_menggabungkan_slot_melebar(): void
    {
        [$kelas, $mapel, $guru] = $this->master('XII RPL');
        $kelas->update(['wali_kelas_id' => $guru->id]);

        // Batas waktu gabungan: 08:00, 08:45 (dari slot Selasa),
        // 09:30, 10:00, 10:40. Slot Senin 08:00-09:30 melebar 2 kolom.
        Jadwal::create([
            'kelas_id' => $kelas->id, 'mapel_id' => $mapel->id, 'guru_id' => $guru->id,
            'hari' => 'senin', 'jam_mulai' => '08:00', 'jam_selesai' => '09:30',
            'semester' => '2026/2027-ganjil', 'aktif' => true,
        ]);
        Jadwal::create([
            'kelas_id' => $kelas->id, 'mapel_id' => $mapel->id, 'guru_id' => $guru->id,
            'hari' => 'senin', 'jam_mulai' => '10:00', 'jam_selesai' => '10:40',
            'semester' => '2026/2027-ganjil', 'aktif' => true,
        ]);
        Jadwal::create([
            'kelas_id' => $kelas->id, 'mapel_id' => $mapel->id, 'guru_id' => $guru->id,
            'hari' => 'selasa', 'jam_mulai' => '08:00', 'jam_selesai' => '08:45',
            'semester' => '2026/2027-ganjil', 'aktif' => true,
        ]);

        $response = $this->actingAs($guru)->get(route('jadwal.index', ['kelas_id' => $kelas->id]));
        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('jadwal/index')
                // Batas: 08:00, 08:45, 09:30, 10:00, 10:40 => 4 kolom.
                ->has('columns', 4)
                ->where('columns.2.mulai', '09:30')
                ->where('columns.2.selesai', '10:00')
                // Kolom 09:30-10:00 tak terisi siapa pun => istirahat.
                ->where('columns.2.istirahat', true)
                ->has('days', 2)
                // Sel pertama Senin = slot 08:00-09:30 dengan span 2.
                ->where('days.0.hari', 'senin')
                ->where('days.0.cells.0.span', 2)
                ->where('days.0.cells.0.jadwal.jam_mulai', '08:00'));
    }

    public function test_halaman_jurnal_menampilkan_slot_dan_waktu_server(): void
    {
        [$kelas, $mapel, $guru] = $this->master('XII RPL');

        Journal::create([
            'guru_id' => $guru->id, 'kelas_id' => $kelas->id, 'mapel_id' => $mapel->id,
            'tanggal' => now()->toDateString(), 'jam_mulai' => '08:00', 'jam_selesai' => '09:30',
            'status' => 'menunggu',
        ]);

        $this->actingAs($guru)->get(route('jurnal.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('jurnal/index')
                ->has('slotHariIni', 1)
                // Waktu server format HH:MM (zona aplikasi, bukan jam HP).
                ->where('sekarang', now()->format('H:i'))
                ->whereNotNull('labelHari'));
    }

    public function test_guru_hanya_bisa_isi_slot_hari_ini(): void
    {
        [$kelas, $mapel, $guru] = $this->master('XII RPL');

        $buatSlot = fn (string $tanggal) => Journal::create([
            'guru_id' => $guru->id, 'kelas_id' => $kelas->id, 'mapel_id' => $mapel->id,
            'tanggal' => $tanggal, 'jam_mulai' => '08:00', 'jam_selesai' => '09:30',
            'status' => 'menunggu',
        ]);

        $kemarin = $buatSlot(now()->subDay()->toDateString());
        $besok = $buatSlot(now()->addDay()->toDateString());
        $hariIni = $buatSlot(now()->toDateString());

        $isi = ['materi' => 'Aljabar', 'kegiatan' => 'Ceramah'];

        // Kemarin & besok: lihat boleh, isi ditolak (arsip tidak bisa ditulis ulang).
        $this->actingAs($guru)->put(route('jurnal.update', $kemarin), $isi)->assertStatus(422);
        $this->actingAs($guru)->put(route('jurnal.update', $besok), $isi)->assertStatus(422);
        $this->actingAs($guru)->post(route('jurnal.izin', $besok), ['catatan' => 'x'])->assertStatus(422);

        // Hari ini: boleh.
        $this->actingAs($guru)->put(route('jurnal.update', $hariIni), $isi)
            ->assertRedirect(route('jurnal.index'));
        $this->assertSame('pending', $hariIni->fresh()->status);

        // Admin boleh koreksi tanggal berapa pun.
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->put(route('jurnal.update', $kemarin), $isi)
            ->assertRedirect(route('jurnal.index'));
    }

    public function test_guru_biasa_tidak_bisa_buka_halaman_jadwal(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->get(route('jadwal.index'))->assertForbidden();
    }

    public function test_index_jurnal_mendukung_jendela_tanggal(): void
    {
        [$kelas, $mapel, $guru] = $this->master('XII RPL');

        Journal::create([
            'guru_id' => $guru->id, 'kelas_id' => $kelas->id, 'mapel_id' => $mapel->id,
            'tanggal' => now()->addDay()->toDateString(), 'jam_mulai' => '08:00', 'jam_selesai' => '09:30',
            'status' => 'menunggu',
        ]);

        // Besok: terlihat. Tanggal jauh di luar jendela: dijepit ke hari ini.
        $this->actingAs($guru)->get(route('jurnal.index', ['tanggal' => now()->addDay()->toDateString()]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('jurnal/index')
                ->has('slotHariIni', 1)
                ->where('tanggalAktif', now()->addDay()->toDateString()));

        $this->actingAs($guru)->get(route('jurnal.index', ['tanggal' => '2020-01-01']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('tanggalAktif', now()->toDateString()));
    }

    public function test_badge_menghitung_hal_bisa_dikerjakan(): void
    {
        [$kelas, $mapel, $guru] = $this->master('XII RPL');
        $mpk = User::factory()->create(['role' => 'mpk', 'kelas_id' => $kelas->id]);

        Journal::create([
            'guru_id' => $guru->id, 'kelas_id' => $kelas->id, 'mapel_id' => $mapel->id,
            'tanggal' => now()->toDateString(), 'jam_mulai' => '08:00', 'jam_selesai' => '09:30',
            'status' => 'menunggu',
        ]);
        Journal::create([
            'guru_id' => $guru->id, 'kelas_id' => $kelas->id, 'mapel_id' => $mapel->id,
            'tanggal' => now()->toDateString(), 'jam_mulai' => '10:00', 'jam_selesai' => '11:30',
            'materi' => 'Aljabar', 'kegiatan' => 'Ceramah', 'status' => 'pending',
        ]);

        // Guru: 1 slot menunggu hari ini. MPK: 1 antrean pending sekelas.
        $this->actingAs($guru)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('unreadCount', 1));
        $this->actingAs($mpk)->get(route('mpk.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('unreadCount', 1));
    }

    /**
     * Bangun file .xlsx asli di folder temp (bukan mock) agar parser
     * Excel benar-benar diuji, termasuk header dan baris kosong.
     *
     * @param  list<list<string>>  $rows
     */
    private function fileExcel(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray(array_merge(
            [['kelas', 'hari', 'jam_mulai', 'jam_selesai', 'kode_mapel', 'email_guru']],
            $rows
        ));
        $path = tempnam(sys_get_temp_dir(), 'jadwal').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile(
            $path, 'jadwal.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null, true
        );
    }

    public function test_template_bisa_diunduh(): void
    {
        [$kelas, , $guru] = $this->master('XII RPL');
        $kelas->update(['wali_kelas_id' => $guru->id]);

        $response = $this->actingAs($guru)->get(route('jadwal.template'));
        $response->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_import_pratinjau_lalu_konfirmasi(): void
    {
        [$kelas] = $this->master('XII RPL');
        $guru = User::factory()->create(['role' => 'guru', 'email' => 'mj@sekolah.test']);
        User::factory()->create(['role' => 'guru', 'email' => 'dn@sekolah.test']);
        $kelas->update(['wali_kelas_id' => $guru->id]);

        // Langkah 1: pratinjau — belum ada yang tersimpan.
        $this->actingAs($guru)->post(route('jadwal.import.preview'), [
            'file' => $this->fileExcel([
                ['XII RPL', 'senin', '08:00', '08:45', 'PWEB', 'dn@sekolah.test'],
                ['XII RPL', 'senin', '08:45', '09:30', 'PWEB', 'dn@sekolah.test'],
            ]),
            'semester' => '2026/2027-ganjil',
        ])->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('jadwal/import')
                ->where('validCount', 2)
                ->has('rows', 2));

        $this->assertSame(0, Jadwal::count());

        // Langkah 2: konfirmasi — baru tertulis.
        $this->actingAs($guru)->post(route('jadwal.import.confirm'))
            ->assertRedirect(route('jadwal.index'));
        $this->assertSame(2, Jadwal::count());

        // Draft sudah dikonsumsi: konfirmasi ulang ditolak.
        $this->actingAs($guru)->post(route('jadwal.import.confirm'))->assertStatus(422);
    }

    public function test_import_menolak_baris_rusak_tanpa_menyimpan(): void
    {
        [$kelas] = $this->master('XII RPL');
        $guru = User::factory()->create(['role' => 'guru', 'email' => 'mj@sekolah.test']);
        $kelas->update(['wali_kelas_id' => $guru->id]);
        ClassRoom::create(['nama_kelas' => 'X LAIN', 'jurusan' => 'Umum', 'tingkat' => 10]);

        $this->actingAs($guru)->post(route('jadwal.import.preview'), [
            'file' => $this->fileExcel([
                ['XII RPL', 'mingguan', '08:00', '08:45', 'PWEB', 'mj@sekolah.test'], // hari salah
                ['XII RPL', 'senin', '08:00', '08:45', 'FISIKA', 'mj@sekolah.test'], // mapel tak dikenal
                ['XII RPL', 'senin', '08:00', '08:45', 'PWEB', 'tak@ada.test'], // guru tak dikenal
                ['X LAIN', 'senin', '08:00', '08:45', 'PWEB', 'mj@sekolah.test'], // bukan kelas binaan
                ['XII RPL', 'senin', '08:00', '08:45', 'PWEB', 'mj@sekolah.test'], // satu-satunya valid
            ]),
            'semester' => '2026/2027-ganjil',
        ])->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('jadwal/import')
                ->where('validCount', 1));

        $this->assertSame(0, Jadwal::count());
    }

    public function test_import_melewati_slot_yang_sudah_ada(): void
    {
        [$kelas, $mapel] = $this->master('XII RPL');
        $guru = User::factory()->create(['role' => 'guru', 'email' => 'mj@sekolah.test']);
        $kelas->update(['wali_kelas_id' => $guru->id]);

        $this->actingAs($guru)->post(route('jadwal.import.preview'), [
            'file' => $this->fileExcel([
                ['XII RPL', 'selasa', '08:00', '08:45', 'PWEB', 'mj@sekolah.test'],
            ]),
            'semester' => '2026/2027-ganjil',
        ])->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('validCount', 1));

        // Slot yang sama dibuat manual SETELAH pratinjau (simulasi balapan):
        // konfirmasi harus melewatinya, bukan dobel.
        Jadwal::create([
            'kelas_id' => $kelas->id, 'mapel_id' => $mapel->id, 'guru_id' => $guru->id,
            'hari' => 'selasa', 'jam_mulai' => '08:00', 'jam_selesai' => '08:45',
            'semester' => '2026/2027-ganjil', 'aktif' => true,
        ]);

        $this->actingAs($guru)->post(route('jadwal.import.confirm'))
            ->assertRedirect(route('jadwal.index'));
        $this->assertSame(1, Jadwal::count());
    }

    public function test_jurnal_tanpa_jadwal_tetap_valid(): void
    {
        // jadwal_id nullable: data lama/uji coba tidak break.
        [$kelas, $mapel, $guru] = $this->master();

        $journal = Journal::create([
            'guru_id' => $guru->id, 'kelas_id' => $kelas->id, 'mapel_id' => $mapel->id,
            'tanggal' => now()->toDateString(), 'jam_mulai' => '08:00', 'jam_selesai' => '09:30',
            'materi' => 'Manual', 'kegiatan' => 'Ceramah', 'status' => 'pending',
        ]);

        $this->assertNull($journal->jadwal_id);
    }
}

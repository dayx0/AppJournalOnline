<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\Jadwal;
use App\Models\Subject;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Seeder;

class JadwalSeeder extends Seeder
{
    /**
     * Jadwal acak untuk SEMUA kelas (48 rombel): Senin-Jumat, 4 slot/hari.
     * Tiap slot: mapel + guru pengampu dipilih acak agar semua kelas
     * dan jurusan kebagian jadwal. Idempotent via updateOrCreate
     * (kunci unik kelas+hari+jam_mulai).
     */
    public function run(): void
    {
        $kelasList = ClassRoom::orderBy('id')->get();
        if ($kelasList->isEmpty()) {
            $this->command->warn('JadwalSeeder: belum ada kelas, lewati.');

            return;
        }

        $mapelIds = Subject::pluck('id')->all();
        if (empty($mapelIds)) {
            $this->command->warn('JadwalSeeder: belum ada mapel, lewati.');

            return;
        }

        $guruIds = User::where('role', 'guru')->pluck('id')->all();
        if (empty($guruIds)) {
            $this->command->warn('JadwalSeeder: tidak ada user guru, lewati.');

            return;
        }

        $aktif = TahunAjaran::aktif();
        $tahun = $aktif?->nama ?? '2026/2027';
        $semesterAktif = $aktif?->semester_aktif ?? 'ganjil';
        $semester = "{$tahun}-{$semesterAktif}";

        // Regenerate penuh: hapus jadwal lama (jurnal aman via nullOnDelete),
        // lalu isi acak untuk semua kelas termasuk XII RPL.
        Jadwal::query()->delete();

        $hariList = ['senin', 'selasa', 'rabu', 'kamis', 'jumat'];
        $slotJam = [
            ['07:00', '08:30'],
            ['08:30', '10:00'],
            ['10:30', '12:00'],
            ['13:00', '14:30'],
        ];

        // Bagi wali merata untuk kelas yang belum punya wali
        // agar tiap grid tetap bisa dikelola via halaman /jadwal.
        $guruIndex = 0;

        foreach ($kelasList as $kelas) {
            if ($kelas->wali_kelas_id === null) {
                $kelas->update(['wali_kelas_id' => $guruIds[$guruIndex % count($guruIds)]]);
                $guruIndex++;
            }

            foreach ($hariList as $hari) {
                $acakMapel = collect($mapelIds)->shuffle()->take(count($slotJam))->values();

                foreach ($slotJam as $i => [$mulai, $selesai]) {
                    Jadwal::updateOrCreate(
                        [
                            'kelas_id' => $kelas->id,
                            'hari' => $hari,
                            'jam_mulai' => $mulai,
                        ],
                        [
                            'mapel_id' => $acakMapel[$i],
                            'guru_id' => $guruIds[array_rand($guruIds)],
                            'jam_selesai' => $selesai,
                            'semester' => $semester,
                            'tahun_ajaran' => $tahun,
                            'aktif' => true,
                        ]
                    );
                }
            }
        }

        $this->command->info(
            "JadwalSeeder: {$kelasList->count()} kelas x ".count($hariList).' hari x '.count($slotJam).' slot terisi.'
        );
    }
}

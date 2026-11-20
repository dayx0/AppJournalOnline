<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\Jadwal;
use App\Models\Subject;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\Concerns\DemoOnlySeeder;
use Illuminate\Database\Seeder;

class DemoJadwalSeeder extends Seeder
{
    use DemoOnlySeeder;

    /**
     * Jadwal acak untuk SEMUA kelas (48 rombel): Senin-Jumat, 4 slot/hari.
     *
     * Hanya untuk demo. Seeder ini TIDAK menghapus atau menimpa jadwal
     * yang sudah ada; slot yang sudah terisi dilewati. Idempotent via
     * firstOrCreate dengan kunci unik kelas+hari+jam_mulai.
     */
    public function run(): void
    {
        $this->ensureDemoEnvironment();

        $kelasList = ClassRoom::orderBy('id')->get();
        if ($kelasList->isEmpty()) {
            $this->command?->warn('DemoJadwalSeeder: belum ada kelas, lewati.');

            return;
        }

        $mapelIds = Subject::pluck('id')->all();
        if (empty($mapelIds)) {
            $this->command?->warn('DemoJadwalSeeder: belum ada mapel, lewati.');

            return;
        }

        $guruIds = User::where('role', 'guru')->pluck('id')->all();
        if (empty($guruIds)) {
            $this->command?->warn('DemoJadwalSeeder: tidak ada user guru, lewati.');

            return;
        }

        $tahun = TahunAjaran::query()->where('is_aktif', true)->value('nama') ?? '2026/2027';
        $semesterAktif = TahunAjaran::query()->where('is_aktif', true)->value('semester_aktif') ?? 'ganjil';
        $semester = "{$tahun}-{$semesterAktif}";

        $hariList = ['senin', 'selasa', 'rabu', 'kamis', 'jumat'];
        $slotJam = [
            ['07:00', '08:30'],
            ['08:30', '10:00'],
            ['10:30', '12:00'],
            ['13:00', '14:30'],
        ];

        // Bagi wali merata untuk kelas yang belum punya wali. Kelas yang
        // sudah punya wali tidak diubah.
        $guruIndex = 0;

        foreach ($kelasList as $kelas) {
            if ($kelas->wali_kelas_id === null) {
                $kelas->update(['wali_kelas_id' => $guruIds[$guruIndex % count($guruIds)]]);
                $guruIndex++;
            }

            foreach ($hariList as $hari) {
                $acakMapel = collect($mapelIds)->shuffle()->take(count($slotJam))->values();

                foreach ($slotJam as $i => [$mulai, $selesai]) {
                    Jadwal::firstOrCreate(
                        [
                            'kelas_id' => $kelas->id,
                            'hari' => $hari,
                            'jam_mulai' => $mulai,
                        ],
                        [
                            'mapel_id' => $acakMapel[$i % $acakMapel->count()],
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

        $this->command?->info(
            "DemoJadwalSeeder: {$kelasList->count()} kelas x ".count($hariList).' hari x '.count($slotJam).' slot (slot lama tidak diubah).'
        );
    }
}

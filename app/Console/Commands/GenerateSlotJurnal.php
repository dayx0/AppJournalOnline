<?php

namespace App\Console\Commands;

use App\Models\Jadwal;
use App\Models\Journal;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateSlotJurnal extends Command
{
    /**
     * Gaya $signature klasik (bukan attribute) karena didukung semua versi
     * Laravel. Dua mode pakai:
     * - Tanpa option: jendela H-1 s.d. H+7 (9 hari, mencakup slider
     *   Kemarin/Hari Ini/Besok + seminggu perencanaan ke depan).
     * - --tanggal=Y-m-d: hanya satu tanggal (untuk test / susulan).
     */
    protected $signature = 'jurnal:generate-slot
        {--tanggal= : Satu tanggal spesifik (Y-m-d); tanpa ini generate jendela mingguan}
        {--mundur=1 : Mundur berapa hari dari hari ini (default 1 = kemarin)}
        {--maju=7 : Maju berapa hari dari hari ini (default 7)}';

    protected $description = 'Generate slot jurnal kosong mingguan dari template jadwal';

    public function handle(): int
    {
        if ($this->option('tanggal')) {
            $this->generateUntuk($this->option('tanggal'));

            return self::SUCCESS;
        }

        $total = 0;
        $mundur = max(0, (int) $this->option('mundur'));
        $maju = max(0, (int) $this->option('maju'));

        // Idempotent per hari (firstOrCreate + unique jadwal_id+tanggal),
        // jadi aman dijalankan tiap hari oleh scheduler: hari baru terbit,
        // hari lama dilewati.
        for ($offset = -$mundur; $offset <= $maju; $offset++) {
            $total += $this->generateUntuk(
                today()->copy()->addDays($offset)->toDateString(),
                quiet: true
            );
        }

        $this->info("Selesai: {$total} slot baru (H-{$mundur} s.d. H+{$maju}).");

        return self::SUCCESS;
    }

    /**
     * Generate slot untuk SATU tanggal. Return jumlah baris baru.
     * Quiet = tanpa output per tanggal (dipakai mode jendela).
     */
    private function generateUntuk(string $tanggal, bool $quiet = false): int
    {
        // isoFormat('dddd') dengan locale id menghasilkan "senin".."minggu"
        // yang persis cocok dengan enum kolom hari. Minggu libur.
        $hari = strtolower(Carbon::parse($tanggal)->locale('id')->isoFormat('dddd'));

        if ($hari === 'minggu') {
            if (! $quiet) {
                $this->info('Hari Minggu: tidak ada slot.');
            }

            return 0;
        }

        $jadwals = Jadwal::where('aktif', true)->where('hari', $hari)->get();

        $dibuat = 0;
        foreach ($jadwals as $jadwal) {
            if ($jadwal->guru_id === null) {
                $this->warn("Jadwal #{$jadwal->id} tanpa guru pengampu, dilewati.");

                continue;
            }

            // firstOrCreate + unique [jadwal_id, tanggal] = idempotent:
            // dijalankan 2x di hari yang sama hasilnya tetap 1 baris.
            if (Journal::firstOrCreate(
                ['jadwal_id' => $jadwal->id, 'tanggal' => $tanggal],
                [
                    'guru_id' => $jadwal->guru_id,
                    'kelas_id' => $jadwal->kelas_id,
                    'mapel_id' => $jadwal->mapel_id,
                    'jam_mulai' => $jadwal->jam_mulai,
                    'jam_selesai' => $jadwal->jam_selesai,
                    'status' => Journal::STATUS_MENUNGGU,
                ]
            )->wasRecentlyCreated) {
                $dibuat++;
            }
        }

        if (! $quiet) {
            $this->info("Selesai: {$dibuat} slot baru untuk {$tanggal}.");
        }

        return $dibuat;
    }
}

<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Journal;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Orkestrasi "Tutup & Buka Tahun Ajaran" untuk Model B (akun MPK milik
 * rombel, diwariskan tiap angkatan).
 *
 * Prinsip: KELAS dan AKUN tidak pernah di-rename/dipindah. Yang berubah
 * hanya (1) status arsip tahun lama, (2) tahun aktif, (3) password MPK
 * (serah terima ke pengurus baru). Semua jalan dalam transaction.
 */
class TahunAjaranService
{
    /**
     * Buka tahun baru + kunci total tahun lama.
     *
     * @return array{baru: TahunAjaran, jurnal_terkunci: int, jadwal_dinonaktifkan: int}
     */
    public static function bukaBaru(string $nama, string $semester): array
    {
        if (! preg_match('/^(\d{4})\/(\d{4})$/', $nama, $m) || (int) $m[2] !== (int) $m[1] + 1) {
            throw ValidationException::withMessages([
                'nama' => 'Format tahun ajaran harus YYYY/YYYY berurutan (contoh: 2026/2027).',
            ]);
        }

        if (! in_array($semester, TahunAjaran::SEMESTERS, true)) {
            throw ValidationException::withMessages([
                'semester' => 'Semester harus ganjil atau genap.',
            ]);
        }

        abort_if(TahunAjaran::where('nama', $nama)->exists(), 422, 'Tahun ajaran ini sudah pernah dibuka.');

        return DB::transaction(function () use ($nama, $semester) {
            $lama = TahunAjaran::aktif();

            $jurnalTerkunci = 0;
            $jadwalOff = 0;

            if ($lama !== null) {
                // Kunci arsip: jurnal lama otomatis read-only via
                // Journal::isTerkunci() (tak perlu update per baris).
                $jurnalTerkunci = Journal::where('tahun_ajaran', $lama->nama)->count();

                // Jadwal lama dinonaktifkan (riwayat grid tetap terlihat
                // di daftar nonaktif, slot baru mulai dari kosong).
                $jadwalOff = Jadwal::where('aktif', true)
                    ->where(function ($q) use ($lama) {
                        $q->where('tahun_ajaran', $lama->nama)->orWhereNull('tahun_ajaran');
                    })
                    ->count();
                Jadwal::where('aktif', true)
                    ->where(function ($q) use ($lama) {
                        $q->where('tahun_ajaran', $lama->nama)->orWhereNull('tahun_ajaran');
                    })
                    ->update(['aktif' => false]);

                $lama->update(['is_aktif' => false, 'locked_at' => now()]);
            }

            $baru = TahunAjaran::create([
                'nama' => $nama,
                'semester_aktif' => $semester,
                'is_aktif' => true,
            ]);

            return [
                'baru' => $baru,
                'jurnal_terkunci' => $jurnalTerkunci,
                'jadwal_dinonaktifkan' => $jadwalOff,
            ];
        });
    }

    /**
     * Reset password massal SEMUA akun MPK (serah terima tahunan).
     * Password acak hanya dikembalikan SEKALI di return ini untuk
     * dicetak/disegel admin — tidak pernah disimpan plain di database.
     *
     * @return list<array{id: int, name: string, email: string, password: string}>
     */
    public static function resetPasswordMpk(): array
    {
        $kredensial = [];

        DB::transaction(function () use (&$kredensial) {
            $mpks = User::where('role', 'mpk')->orderBy('email')->get();

            foreach ($mpks as $mpk) {
                $plain = Str::random(10);

                $mpk->update([
                    'password' => Hash::make($plain),
                    'must_change_password' => true,
                ]);

                $kredensial[] = [
                    'id' => $mpk->id,
                    'name' => $mpk->name,
                    'email' => $mpk->email,
                    'password' => $plain,
                ];
            }
        });

        return $kredensial;
    }
}

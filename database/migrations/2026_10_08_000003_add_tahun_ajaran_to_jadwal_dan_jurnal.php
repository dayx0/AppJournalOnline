<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pemisah data antar tahun ajaran (poin D). Kolom nullable agar kode
     * lama/test lama tetap jalan; nilai diisi otomatis oleh model
     * (creating hook) atau backfill di bawah untuk data existing.
     *
     * Sengaja TIDAK mengubah tipe kolom jadwal.semester (tetap string
     * bebas seperti '2026/2027-ganjil') agar seeder/test lama tidak pecah;
     * normalisasi semester jadi tanggung jawab validasi input baru.
     */
    public function up(): void
    {
        Schema::table('jadwal', function (Blueprint $table) {
            $table->string('tahun_ajaran', 9)->nullable()->after('semester');
            $table->index(['tahun_ajaran', 'aktif']);
        });

        Schema::table('jurnal', function (Blueprint $table) {
            $table->string('tahun_ajaran', 9)->nullable()->after('tanggal');
            $table->string('semester', 6)->nullable()->after('tahun_ajaran');
            $table->index(['tahun_ajaran', 'semester', 'kelas_id']);
        });

        // Backfill jadwal: parse pola lama 'YYYY/YYYY-ganjil|genap'.
        foreach (DB::table('jadwal')->select('id', 'semester')->get() as $row) {
            $tahun = $this->parseTahunDariSemester((string) $row->semester) ?? '2026/2027';
            DB::table('jadwal')->where('id', $row->id)->update(['tahun_ajaran' => $tahun]);
        }

        // Backfill jurnal dari tanggal: Juli-Juni = satu tahun ajaran,
        // Jul-Des = ganjil, Jan-Jun = genap.
        foreach (DB::table('jurnal')->select('id', 'tanggal')->get() as $row) {
            DB::table('jurnal')->where('id', $row->id)->update([
                'tahun_ajaran' => $this->tahunUntukTanggal((string) $row->tanggal),
                'semester' => $this->semesterUntukTanggal((string) $row->tanggal),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('jurnal', function (Blueprint $table) {
            $table->dropIndex(['tahun_ajaran', 'semester', 'kelas_id']);
            $table->dropColumn(['tahun_ajaran', 'semester']);
        });

        Schema::table('jadwal', function (Blueprint $table) {
            $table->dropIndex(['tahun_ajaran', 'aktif']);
            $table->dropColumn('tahun_ajaran');
        });
    }

    private function parseTahunDariSemester(string $semester): ?string
    {
        if (preg_match('/^(\d{4}\/\d{4})-(ganjil|genap)$/', trim($semester), $m)) {
            return $m[1];
        }

        return null;
    }

    private function tahunUntukTanggal(string $tanggal): string
    {
        $t = Carbon::parse($tanggal);

        return $t->month >= 7
            ? $t->year.'/'.($t->year + 1)
            : ($t->year - 1).'/'.$t->year;
    }

    private function semesterUntukTanggal(string $tanggal): string
    {
        return Carbon::parse($tanggal)->month >= 7 ? 'ganjil' : 'genap';
    }
};

<?php

use App\Models\Jurusan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pastikan master ada (migrasi harus self-contained).
        if (Schema::hasTable('jurusans')) {
            foreach (Jurusan::MASTER as $row) {
                Jurusan::firstOrCreate(['kode' => $row['kode']], $row);
            }
        }

        Schema::table('kelas', function (Blueprint $table) {
            $table->foreignId('jurusan_id')->nullable()->after('tingkat')
                ->constrained('jurusans')->nullOnDelete();
        });

        // Backfill: kode diambil dari nama_kelas ("X AKL 1" -> AKL),
        // fallback ke isi kolom jurusan lama ("RPL", "IPA", ...).
        // Kode tak dikenal dibuatkan baris jurusan baru agar tak ada data hilang.
        $kodeToId = Jurusan::pluck('id', 'kode')->all();
        // Normalisasi key ke uppercase (SQLite collation bisa case-sensitive).
        $map = [];
        foreach ($kodeToId as $kode => $id) {
            $map[strtoupper($kode)] = $id;
        }

        $kelasRows = DB::table('kelas')->select('id', 'nama_kelas', 'jurusan')->get();
        foreach ($kelasRows as $row) {
            $kode = $this->tebakKode($row->nama_kelas ?? '', $row->jurusan ?? '');
            if ($kode === null) {
                continue;
            }
            if (! isset($map[$kode])) {
                $id = DB::table('jurusans')->insertGetId([
                    'kode' => $kode,
                    'nama' => $row->jurusan ? (string) $row->jurusan : $kode,
                    'kelompok' => $kode,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $map[$kode] = $id;
            }
            DB::table('kelas')->where('id', $row->id)->update(['jurusan_id' => $map[$kode]]);
        }

        // Hapus kolom string lama: satu-satunya sumber = jurusan_id.
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropColumn('jurusan');
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->string('jurusan', 100)->nullable()->after('nama_kelas');
        });

        // Kembalikan label dari relasi (kode jurusan).
        $rows = DB::table('kelas')->select('kelas.id', 'jurusans.kode')->leftJoin('jurusans', 'jurusans.id', '=', 'kelas.jurusan_id')->get();
        foreach ($rows as $row) {
            DB::table('kelas')->where('id', $row->id)->update(['jurusan' => $row->kode ?? '']);
        }

        Schema::table('kelas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jurusan_id');
        });
    }

    /**
     * "XII BD 1" -> BD, "X PPLG" -> PPLG, "X-A" -> fallback jurusan lama.
     */
    private function tebakKode(string $namaKelas, string $jurusanLama): ?string
    {
        $parts = preg_split('/\s+/', trim($namaKelas));
        if (is_array($parts) && count($parts) >= 2 && preg_match('/^[A-Z]{2,4}$/i', (string) $parts[1])) {
            return strtoupper((string) $parts[1]);
        }

        $lama = strtoupper(trim($jurusanLama));
        if ($lama !== '') {
            // Kasus lama "X PPLG" ber-jurusan "RPL": nama lebih benar dari label.
            return $lama;
        }

        return null;
    }
};

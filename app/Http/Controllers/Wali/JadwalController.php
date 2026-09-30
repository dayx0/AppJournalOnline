<?php

namespace App\Http\Controllers\Wali;

use App\Exports\JadwalTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\JadwalRowsImport;
use App\Models\ClassRoom;
use App\Models\Jadwal;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class JadwalController extends Controller
{
    private function kelasOptions(Request $request)
    {
        // PENTING: wali hanya dikirimi kelasnya. Filter di backend,
        // bukan di frontend — frontend tidak boleh dipercaya.
        return $request->user()->isAdmin()
            ? ClassRoom::orderBy('nama_kelas')->get(['id', 'nama_kelas'])
            : ClassRoom::where('wali_kelas_id', $request->user()->id)
                ->orderBy('nama_kelas')->get(['id', 'nama_kelas']);
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Jadwal::class);

        // Grid satu kelas penuh (seperti tabel dinding): wali terkunci di
        // kelas binaannya, admin memilih kelas (default XII RPL = fokus saat ini).
        $bolehLihat = $request->user()->isAdmin()
            ? ClassRoom::orderBy('nama_kelas')->pluck('id')->all()
            : ClassRoom::where('wali_kelas_id', $request->user()->id)
                ->orderBy('nama_kelas')->pluck('id')->all();

        $diminta = $request->filled('kelas_id') ? (int) $request->kelas_id : null;

        // Prioritas: pilihan user -> XII RPL (fokus saat ini) -> kelas pertama.
        // Pilihan di luar hak (mis. wali mengintip kelas lain) jatuh ke default.
        $kelasId = null;
        if ($diminta !== null && in_array($diminta, $bolehLihat, true)) {
            $kelasId = $diminta;
        } else {
            $xiiId = (int) (ClassRoom::where('nama_kelas', 'XII RPL')->value('id') ?? 0);
            $kelasId = in_array($xiiId, $bolehLihat, true) ? $xiiId : ($bolehLihat[0] ?? null);
        }

        $slots = $kelasId === null
            ? collect()
            : Jadwal::with(['kelas:id,nama_kelas', 'mataPelajaran:id,nama_mapel,kode_mapel', 'guru:id,name'])
                ->where('kelas_id', $kelasId)
                ->where('aktif', true)
                ->get();

        // Kolom grid = pasangan batas waktu berurutan dari semua slot.
        // Slot yang melintasi beberapa kolom otomatis bergabung (colspan),
        // celah yang tak terisi siapa pun = kolom istirahat (seperti gambar).
        $batas = $slots
            ->flatMap(fn ($j) => [substr($j->jam_mulai, 0, 5), substr($j->jam_selesai, 0, 5)])
            ->unique()->sort()->values();

        $columns = [];
        for ($i = 0; $i < $batas->count() - 1; $i++) {
            $mulai = $batas[$i];
            $selesai = $batas[$i + 1];
            $terisi = $slots->contains(
                fn ($j) => substr($j->jam_mulai, 0, 5) <= $mulai && substr($j->jam_selesai, 0, 5) >= $selesai
            );
            $columns[] = ['mulai' => $mulai, 'selesai' => $selesai, 'istirahat' => ! $terisi];
        }

        // Baris per hari: sel slot digabung (span), sel kosong = strip.
        $days = [];
        foreach (array_keys(Jadwal::HARI) as $hari) {
            $milikHari = $slots->where('hari', $hari)->sortBy('jam_mulai')->values();
            if ($milikHari->isEmpty()) {
                continue;
            }

            $cells = [];
            $i = 0;
            while ($i < count($columns)) {
                $c = $columns[$i];
                $slot = $milikHari->first(
                    fn ($j) => substr($j->jam_mulai, 0, 5) <= $c['mulai']
                        && substr($j->jam_selesai, 0, 5) >= $c['selesai']
                );

                if ($slot === null) {
                    $cells[] = ['jadwal' => null, 'span' => 1, 'istirahat' => $c['istirahat']];
                    $i++;

                    continue;
                }

                // Lebarkan sel selama kolom berikut masih milik slot yang sama.
                $span = 1;
                while (
                    $i + $span < count($columns)
                    && substr($slot->jam_mulai, 0, 5) <= $columns[$i + $span]['mulai']
                    && substr($slot->jam_selesai, 0, 5) >= $columns[$i + $span]['selesai']
                ) {
                    $span++;
                }

                $cells[] = ['jadwal' => $slot, 'span' => $span, 'istirahat' => false];
                $i += $span;
            }

            $days[] = ['hari' => $hari, 'label' => Jadwal::HARI[$hari], 'cells' => $cells];
        }

        $nonaktif = $kelasId === null
            ? collect()
            : Jadwal::with(['mataPelajaran:id,nama_mapel,kode_mapel', 'guru:id,name'])
                ->where('kelas_id', $kelasId)
                ->where('aktif', false)
                ->orderBy('hari')->orderBy('jam_mulai')
                ->get();

        return Inertia::render('jadwal/index', [
            'kelasAktif' => $kelasId === null ? null : ClassRoom::find($kelasId)?->only(['id', 'nama_kelas']),
            'columns' => $columns,
            'days' => $days,
            'total' => $slots->count(),
            'nonaktif' => $nonaktif,
            'kelas' => $this->kelasOptions($request),
            'mapel' => Subject::orderBy('nama_mapel')->get(['id', 'nama_mapel']),
            'guru' => User::where('role', 'guru')->orderBy('name')->get(['id', 'name']),
            'hari' => Jadwal::HARI,
            'filters' => $request->only(['kelas_id']),
            // Konteks untuk kalimat panduan ("kelas binaan: X").
            'kelasSaya' => $request->user()->isAdmin()
                ? null
                : ClassRoom::where('wali_kelas_id', $request->user()->id)
                    ->orderBy('nama_kelas')->get(['id', 'nama_kelas']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'mapel_id' => 'required|exists:mata_pelajaran,id',
            'guru_id' => 'required|exists:users,id',
            'hari' => ['required', Rule::in(array_keys(Jadwal::HARI))],
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'semester' => 'required|string|max:20',
            'aktif' => 'nullable|boolean',
        ]);

        $kelas = ClassRoom::findOrFail($validated['kelas_id']);
        $this->authorize('create', [Jadwal::class, $kelas]);
        $this->assertTidakBentrok($validated);

        Jadwal::create($validated);

        return back()->with('success', 'Jadwal berhasil ditambah');
    }

    public function update(Request $request, Jadwal $jadwal)
    {
        $this->authorize('update', $jadwal);

        // Aturan validasi SAMA PERSIS dengan store(): update tidak boleh
        // lebih longgar dari create, kalau tidak user bisa mengakali
        // batasan lewat form edit (mis. jam_selesai < jam_mulai).
        $validated = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'mapel_id' => 'required|exists:mata_pelajaran,id',
            'guru_id' => 'required|exists:users,id',
            'hari' => ['required', Rule::in(array_keys(Jadwal::HARI))],
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'semester' => 'required|string|max:20',
            'aktif' => 'nullable|boolean',
        ]);

        $this->authorize('create', [Jadwal::class, ClassRoom::findOrFail($validated['kelas_id'])]);
        $this->assertTidakBentrok($validated, $jadwal->id);

        $jadwal->update($validated);

        return back()->with('success', 'Jadwal diperbarui');
    }

    public function destroy(Jadwal $jadwal)
    {
        $this->authorize('delete', $jadwal);
        // Jurnal yang sudah terbit TIDAK ikut hapus (nullOnDelete):
        // jadwal_id-nya jadi NULL tapi riwayat jurnal aman.
        $jadwal->delete();

        return back()->with('success', 'Jadwal dihapus');
    }

    public function template(): BinaryFileResponse
    {
        $this->authorize('viewAny', Jadwal::class);

        return Excel::download(new JadwalTemplateExport, 'template-jadwal.xlsx');
    }

    /**
     * Langkah 1 import: baca file, validasi per baris, tampilkan draft.
     * TIDAK ada yang ditulis ke database di sini — user konfirmasi dulu
     * di halaman pratinjau (lihat importConfirm).
     */
    public function importPreview(Request $request): Response
    {
        $this->authorize('viewAny', Jadwal::class);

        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
            'semester' => 'required|string|max:20',
        ]);

        $reader = new JadwalRowsImport;
        Excel::import($reader, $request->file('file'));
        $sheets = [$reader->rows];
        $baris = $sheets[0] ?? [];

        $bolehKelas = $request->user()->isAdmin()
            ? ClassRoom::pluck('id')->all()
            : ClassRoom::where('wali_kelas_id', $request->user()->id)->pluck('id')->all();

        $hasil = $this->validasiBarisImport($baris, $bolehKelas, $validated['semester']);

        // Simpan yang valid di session untuk langkah konfirmasi.
        // Re-autorisasi tetap dilakukan di importConfirm (session bisa usang).
        session()->put('jadwal_import', [
            'semester' => $validated['semester'],
            'rows' => array_column(array_filter($hasil, fn ($r) => empty($r['errors'])), 'data'),
        ]);

        return Inertia::render('jadwal/import', [
            'rows' => $hasil,
            'semester' => $validated['semester'],
            'validCount' => count(array_filter($hasil, fn ($r) => empty($r['errors']))),
        ]);
    }

    /**
     * Langkah 2 import: tulis draft valid ke database.
     * firstOrCreate (bukan create): baris yang sudah ada dilewati,
     * dilaporkan sebagai "dilewati" agar user tahu.
     */
    public function importConfirm(Request $request)
    {
        $this->authorize('viewAny', Jadwal::class);

        $draft = session()->pull('jadwal_import');
        abort_unless(! empty($draft['rows'] ?? []), 422, 'Tidak ada draft import. Unggah ulang filenya.');

        $dibuat = 0;
        $dilewati = 0;

        foreach ($draft['rows'] as $row) {
            $kelas = ClassRoom::find($row['kelas_id']);
            abort_unless($kelas, 422, 'Kelas draft tidak ditemukan.');
            // Policy per baris: wali tak bisa menyelundupkan kelas lain
            // lewat session yang dimodifikasi manual.
            $this->authorize('create', [Jadwal::class, $kelas]);

            $jadwal = Jadwal::firstOrCreate(
                ['kelas_id' => $row['kelas_id'], 'hari' => $row['hari'], 'jam_mulai' => $row['jam_mulai']],
                [...$row, 'semester' => $draft['semester'], 'aktif' => true]
            );

            $jadwal->wasRecentlyCreated ? $dibuat++ : $dilewati++;
        }

        return redirect()->route('jadwal.index')
            ->with('success', "Import selesai: {$dibuat} dibuat, {$dilewati} dilewati (sudah ada).");
    }

    /**
     * Validasi isi file Excel per baris. Mengembalikan daftar:
     * [['no' => 2, 'data' => [...]|null, 'errors' => [...]], ...]
     * 'no' = nomor baris Excel (header = 1) agar mudah dicari user.
     */
    private function validasiBarisImport(array $baris, array $bolehKelas, string $semester): array
    {
        if (empty($baris)) {
            return [['no' => 0, 'data' => null, 'errors' => ['File kosong.']]];
        }

        // Petakan header (case-insensitive) ke kolom yang dikenal.
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $baris[0]);
        $kolom = ['kelas' => null, 'hari' => null, 'jam_mulai' => null, 'jam_selesai' => null, 'kode_mapel' => null, 'email_guru' => null];
        foreach ($kolom as $nama => $v) {
            $idx = array_search($nama, $header, true);
            $kolom[$nama] = $idx === false ? null : $idx;
        }

        $hilang = array_keys(array_filter($kolom, fn ($v) => $v === null));
        if (! empty($hilang)) {
            return [['no' => 1, 'data' => null, 'errors' => ['Header kurang: '.implode(', ', $hilang).'. Unduh template dulu.']]];
        }

        $mapelIds = Subject::pluck('id', 'kode_mapel')->all();
        $kelasIds = ClassRoom::pluck('id', 'nama_kelas')->all();
        $guruIds = User::where('role', 'guru')->pluck('id', 'email')->all();

        $hasil = [];
        $diterima = []; // slot valid file-ini (untuk cek bentrok antar-baris)

        foreach (array_slice($baris, 1) as $i => $row) {
            $no = $i + 2; // baris Excel (header = 1)
            $sel = fn (string $nama) => isset($row[$kolom[$nama]]) ? trim((string) $row[$kolom[$nama]]) : '';

            // Lewati baris kosong total (akhir file Excel sering berlebih).
            if ($sel('kelas') === '' && $sel('hari') === '' && $sel('jam_mulai') === '' && $sel('jam_selesai') === '' && $sel('kode_mapel') === '' && $sel('email_guru') === '') {
                continue;
            }

            $errors = [];
            $hari = strtolower($sel('hari'));
            $mulai = $this->normalisasiJam($row[$kolom['jam_mulai']] ?? null);
            $selesai = $this->normalisasiJam($row[$kolom['jam_selesai']] ?? null);

            if (! isset(Jadwal::HARI[$hari])) {
                $errors[] = "Hari '{$sel('hari')}' tidak dikenal (pakai: ".implode(', ', array_keys(Jadwal::HARI)).').';
            }
            if ($mulai === null) {
                $errors[] = "Jam mulai '{$sel('jam_mulai')}' tidak valid (format JJ:MM).";
            }
            if ($selesai === null) {
                $errors[] = "Jam selesai '{$sel('jam_selesai')}' tidak valid (format JJ:MM).";
            }
            if ($mulai !== null && $selesai !== null && $selesai <= $mulai) {
                $errors[] = 'Jam selesai harus setelah jam mulai.';
            }

            $kelasId = $kelasIds[$sel('kelas')] ?? null;
            if ($kelasId === null) {
                $errors[] = "Kelas '{$sel('kelas')}' tidak dikenal.";
            } elseif (! in_array($kelasId, $bolehKelas, true)) {
                $errors[] = "Kelas '{$sel('kelas')}' bukan kelas binaanmu.";
            }

            $mapelId = $mapelIds[$sel('kode_mapel')] ?? null;
            if ($mapelId === null) {
                $errors[] = "Kode mapel '{$sel('kode_mapel')}' tidak dikenal.";
            }

            $guruId = $guruIds[strtolower($sel('email_guru'))] ?? $guruIds[$sel('email_guru')] ?? null;
            if ($guruId === null) {
                $errors[] = "Email guru '{$sel('email_guru')}' tidak terdaftar sebagai guru.";
            }

            $data = null;
            if (empty($errors)) {
                $data = [
                    'kelas_id' => $kelasId, 'hari' => $hari,
                    'jam_mulai' => $mulai, 'jam_selesai' => $selesai,
                    'mapel_id' => $mapelId, 'guru_id' => $guruId,
                ];

                if (
                    $this->slotBentrok($data)
                    || $this->bentrokDengan($data, $diterima)
                ) {
                    $errors[] = 'Bentrok dengan slot lain (database/file).';
                    $data = null;
                } else {
                    $diterima[] = $data;
                }
            }

            $hasil[] = ['no' => $no, 'data' => $data, 'errors' => $errors];
        }

        return $hasil;
    }

    /** Cek bentrok terhadap slot valid lain dalam file yang sama. */
    private function bentrokDengan(array $data, array $diterima): bool
    {
        foreach ($diterima as $lain) {
            if (
                $lain['kelas_id'] === $data['kelas_id']
                && $lain['hari'] === $data['hari']
                && $lain['jam_mulai'] < $data['jam_selesai']
                && $lain['jam_selesai'] > $data['jam_mulai']
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalisasi sel jam Excel ke 'HH:MM'. Excel menyimpan jam sebagai
     * pecahan hari (0.33 = ~08:00) bila sel diformat waktu — konversi itu
     * dulu sebelum baca sebagai teks.
     */
    private function normalisasiJam(mixed $nilai): ?string
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        if (is_numeric($nilai)) {
            try {
                return Carbon::instance(
                    Date::excelToDateTimeObject((float) $nilai)
                )->format('H:i');
            } catch (\Throwable) {
                return null;
            }
        }

        $s = trim((string) $nilai);
        if (
            preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $s, $m)
            && (int) $m[1] < 24 && (int) $m[2] < 60
        ) {
            return str_pad($m[1], 2, '0', STR_PAD_LEFT).':'.$m[2];
        }

        return null;
    }

    /**
     * Dua rentang [A,B) dan [C,D) bersinggungan jika A < D DAN C < B.
     * Aturan bisnis yang tak bisa diungkap rule bawaan -> cek manual.
     * Dipakai form (lempar error) dan import Excel (kumpulkan error).
     */
    private function slotBentrok(array $data, ?int $kecualiId = null): bool
    {
        return Jadwal::where('kelas_id', $data['kelas_id'])
            ->where('hari', $data['hari'])
            ->where('aktif', true)
            ->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId))
            ->where('jam_mulai', '<', $data['jam_selesai'])
            ->where('jam_selesai', '>', $data['jam_mulai'])
            ->exists();
    }

    private function assertTidakBentrok(array $data, ?int $kecualiId = null): void
    {
        if ($this->slotBentrok($data, $kecualiId)) {
            throw ValidationException::withMessages([
                'jam_mulai' => 'Slot bentrok dengan jadwal lain di kelas & hari yang sama.',
            ]);
        }
    }
}

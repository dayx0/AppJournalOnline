<?php

namespace App\Http\Controllers\Mpk;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Journal;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RekapController extends Controller
{
    /**
     * Filter yang didukung: dari, sampai, kelas_id, mapel_id, guru_id, status.
     *
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return $request->validate([
            'dari' => 'nullable|date',
            'sampai' => 'nullable|date|after_or_equal:dari',
            'kelas_id' => 'nullable|integer|exists:kelas,id',
            'mapel_id' => 'nullable|integer|exists:mata_pelajaran,id',
            'guru_id' => 'nullable|integer|exists:users,id',
            'status' => 'nullable|in:menunggu,pending,divalidasi,ditolak,jam_kosong,izin,terlambat',
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function baseQuery(array $filters, ?int $kelasId): Builder
    {
        $query = Journal::with(['guru', 'kelas', 'mataPelajaran', 'validator']);

        // Kunci utama fitur ini: MPK hanya bisa melihat kelasnya sendiri.
        // MPK tanpa kelas (null) tidak melihat apapun.
        if ($kelasId === null) {
            return $query->whereRaw('0 = 1');
        }
        $query->where('kelas_id', $kelasId);

        if (! empty($filters['dari'])) {
            $query->whereDate('tanggal', '>=', $filters['dari']);
        }

        if (! empty($filters['sampai'])) {
            $query->whereDate('tanggal', '<=', $filters['sampai']);
        }

        foreach (['mapel_id' => 'mapel_id', 'guru_id' => 'guru_id'] as $key => $column) {
            if (! empty($filters[$key])) {
                $query->where($column, $filters[$key]);
            }
        }
        // Catatan: filter kelas_id dari request sengaja diabaikan —
        // MPK selalu dikunci ke kelasnya sendiri (lihat where di atas).

        return $query;
    }

    private function mpkKelasId(Request $request): ?int
    {
        $kelasId = $request->user()->kelas_id;

        return $kelasId === null ? null : (int) $kelasId;
    }

    public function index(Request $request): Response
    {
        $filters = $this->filters($request);
        $kelasId = $this->mpkKelasId($request);
        $query = $this->baseQuery($filters, $kelasId);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $counter = $this->baseQuery($filters, $kelasId);

        return Inertia::render('mpk/rekap', [
            'jurnals' => $query->orderByDesc('tanggal')->orderByDesc('id')->paginate(15)->withQueryString(),
            'filters' => $request->only(['dari', 'sampai', 'kelas_id', 'mapel_id', 'guru_id', 'status']),
            'summary' => [
                'total' => (clone $counter)->count(),
                'menunggu' => (clone $counter)->where('status', Journal::STATUS_MENUNGGU)->count(),
                'pending' => (clone $counter)->where('status', Journal::STATUS_PENDING)->count(),
                'divalidasi' => (clone $counter)->where('status', Journal::STATUS_DIVALIDASI)->count(),
                'ditolak' => (clone $counter)->where('status', Journal::STATUS_DITOLAK)->count(),
                'jam_kosong' => (clone $counter)->where('status', Journal::STATUS_JAM_KOSONG)->count(),
                'izin' => (clone $counter)->where('status', Journal::STATUS_IZIN)->count(),
                'terlambat' => (clone $counter)->where('status', Journal::STATUS_TERLAMBAT)->count(),
            ],
            'options' => [
                // Dropdown kelas hanya menampilkan kelas binaan MPK ini.
                'kelas' => ClassRoom::when($kelasId === null, fn ($q) => $q->whereRaw('0 = 1'))
                    ->when($kelasId !== null, fn ($q) => $q->where('id', $kelasId))
                    ->orderBy('nama_kelas')->get(['id', 'nama_kelas']),
                'mapel' => Subject::orderBy('nama_mapel')->get(['id', 'nama_mapel']),
                'guru' => User::where('role', 'guru')->orderBy('name')->get(['id', 'name']),
            ],
            'assignedKelas' => $request->user()->kelas?->only(['id', 'nama_kelas']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $query = $this->baseQuery($filters, $this->mpkKelasId($request));

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $filename = 'rekap-jurnal-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            // BOM agar Excel Indonesia membuka UTF-8 dengan benar
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Tanggal', 'Jam', 'Guru', 'Kelas', 'Mata Pelajaran',
                'Materi', 'Status', 'Divalidasi Oleh', 'Waktu Validasi', 'Catatan Validasi',
            ], ';');

            $query->orderByDesc('tanggal')->orderByDesc('id')->chunk(500, function ($jurnals) use ($out) {
                foreach ($jurnals as $j) {
                    fputcsv($out, [
                        $j->tanggal,
                        substr((string) $j->jam_mulai, 0, 5).' - '.substr((string) $j->jam_selesai, 0, 5),
                        $j->guru?->name ?? '-',
                        $j->kelas?->nama_kelas ?? '-',
                        $j->mataPelajaran?->nama_mapel ?? '-',
                        $j->materi,
                        $j->status,
                        $j->validator?->name ?? '-',
                        $j->validated_at?->format('Y-m-d H:i') ?? '-',
                        $j->validation_note ?? '-',
                    ], ';');
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

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
            'status' => 'nullable|in:pending,divalidasi,revisi',
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function baseQuery(array $filters, ?int $kelasId): Builder
    {
        $query = Journal::with(['guru', 'kelas', 'mataPelajaran', 'validator']);

        // MPK perkelas: selalu kunci ke kelas milik MPK.
        // MPK tanpa kelas -> tidak melihat apa-apa.
        if (empty($kelasId)) {
            return $query->whereRaw('0 = 1');
        }
        $query->where('kelas_id', $kelasId);

        if (! empty($filters['dari'])) {
            $query->whereDate('tanggal', '>=', $filters['dari']);
        }

        if (! empty($filters['sampai'])) {
            $query->whereDate('tanggal', '<=', $filters['sampai']);
        }

        foreach (['kelas_id' => 'kelas_id', 'mapel_id' => 'mapel_id', 'guru_id' => 'guru_id'] as $key => $column) {
            if (! empty($filters[$key])) {
                $query->where($column, $filters[$key]);
            }
        }

        return $query;
    }

    public function index(Request $request): Response
    {
        $filters = $this->filters($request);
        $kelasId = $request->user()->mpkKelasId();
        // Abaikan filter kelas_id dari request: MPK terkunci ke kelasnya.
        $filters['kelas_id'] = $kelasId;
        $query = $this->baseQuery($filters, $kelasId ? (int) $kelasId : null);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $counter = $this->baseQuery($filters, $kelasId ? (int) $kelasId : null);

        return Inertia::render('mpk/rekap', [
            'jurnals' => $query->orderByDesc('tanggal')->orderByDesc('id')->paginate(15)->withQueryString(),
            'filters' => $request->only(['dari', 'sampai', 'kelas_id', 'mapel_id', 'guru_id', 'status']),
            'summary' => [
                'total' => (clone $counter)->count(),
                'pending' => (clone $counter)->where('status', 'pending')->count(),
                'divalidasi' => (clone $counter)->where('status', 'divalidasi')->count(),
                'revisi' => (clone $counter)->where('status', 'revisi')->count(),
            ],
            'options' => [
                'kelas' => $kelasId
                    ? ClassRoom::where('id', $kelasId)->get(['id', 'nama_kelas'])
                    : [],
                'mapel' => Subject::orderBy('nama_mapel')->get(['id', 'nama_mapel']),
                'guru' => User::where('role', 'guru')->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $kelasId = $request->user()->mpkKelasId();
        $filters['kelas_id'] = $kelasId;
        $query = $this->baseQuery($filters, $kelasId ? (int) $kelasId : null);

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

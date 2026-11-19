<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nama
 * @property string $semester_aktif
 * @property bool $is_aktif
 * @property Carbon|null $locked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TahunAjaran extends Model
{
    protected $table = 'tahun_ajarans';

    protected $fillable = ['nama', 'semester_aktif', 'is_aktif', 'locked_at'];

    public const array SEMESTERS = ['ganjil', 'genap'];

    protected function casts(): array
    {
        return [
            'is_aktif' => 'boolean',
            'locked_at' => 'datetime',
        ];
    }

    /** Tahun ajaran yang sedang berjalan (maksimal satu). */
    public static function aktif(): ?self
    {
        return static::where('is_aktif', true)->first();
    }

    /** Baris tahun ini sendiri berstatus arsip terkunci. */
    public function isArsip(): bool
    {
        return $this->locked_at !== null;
    }

    /**
     * Heuristik kalender akademik Indonesia: Juli-Juni = satu tahun ajaran.
     * Dipakai untuk backfill & cap otomatis (bukan untuk bypass validasi).
     */
    public static function namaUntukTanggal(string $tanggal): string
    {
        $t = Carbon::parse($tanggal);

        return $t->month >= 7
            ? $t->year.'/'.($t->year + 1)
            : ($t->year - 1).'/'.$t->year;
    }

    public static function semesterUntukTanggal(string $tanggal): string
    {
        return Carbon::parse($tanggal)->month >= 7 ? 'ganjil' : 'genap';
    }

    /**
     * Kunci total read-only: terkunci bila (a) tidak ada tahun aktif
     * (mode lama/test -> semua boleh, return false), atau (b) tahun beda
     * dari yang aktif, atau (c) tahun sama tapi semester sudah lewat
     * (semester aktif sudah berganti). Null = data legacy -> boleh.
     */
    public static function isTerkunci(?string $tahunAjaran, ?string $semester = null): bool
    {
        if ($tahunAjaran === null) {
            return false;
        }

        $aktif = static::aktif();

        if ($aktif === null) {
            return false;
        }

        if ($tahunAjaran !== $aktif->nama) {
            return true;
        }

        return $semester !== null && $semester !== $aktif->semester_aktif;
    }

    /** Varian khusus jadwal (jadwal tak punya kolom semester enum). */
    public static function isTahunTerkunci(?string $tahunAjaran): bool
    {
        return static::isTerkunci($tahunAjaran);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jurusan extends Model
{
    protected $table = 'jurusans';

    protected $fillable = ['kode', 'nama', 'kelompok'];

    /**
     * 11 kode resmi. Kelompok = rumpun "satu jurusan" yang sama
     * walau namanya beda per tingkat (AKL<->AK, MPLB<->MP, PPLG<->RPL)
     * dan PM yang pecah jadi BD/BR di XI/XII.
     */
    public const array MASTER = [
        ['kode' => 'AKL', 'nama' => 'Akuntansi dan Keuangan Lembaga', 'kelompok' => 'AK'],
        ['kode' => 'AK', 'nama' => 'Akuntansi', 'kelompok' => 'AK'],
        ['kode' => 'MPLB', 'nama' => 'Manajemen Perkantoran dan Layanan Bisnis', 'kelompok' => 'MP'],
        ['kode' => 'MP', 'nama' => 'Manajemen Perkantoran', 'kelompok' => 'MP'],
        ['kode' => 'PM', 'nama' => 'Pemasaran', 'kelompok' => 'PM'],
        ['kode' => 'BD', 'nama' => 'Bisnis Digital', 'kelompok' => 'PM'],
        ['kode' => 'BR', 'nama' => 'Bisnis Ritel', 'kelompok' => 'PM'],
        ['kode' => 'DKV', 'nama' => 'Desain Komunikasi Visual', 'kelompok' => 'DKV'],
        ['kode' => 'TJKT', 'nama' => 'Teknik Jaringan Komputer dan Telekomunikasi', 'kelompok' => 'TJKT'],
        ['kode' => 'PPLG', 'nama' => 'Pengembangan Perangkat Lunak dan Gim', 'kelompok' => 'RPL'],
        ['kode' => 'RPL', 'nama' => 'Rekayasa Perangkat Lunak', 'kelompok' => 'RPL'],
    ];

    /**
     * Kode yang boleh dipilih per tingkat.
     * X: AKL, MPLB, PM, DKV, TJKT, PPLG (16 rombel).
     * XI/XII: AK, MP, BD, BR, DKV, TJKT, RPL (16 rombel).
     */
    public const array ALLOWED_BY_TINGKAT = [
        10 => ['AKL', 'MPLB', 'PM', 'DKV', 'TJKT', 'PPLG'],
        11 => ['AK', 'MP', 'BD', 'BR', 'DKV', 'TJKT', 'RPL'],
        12 => ['AK', 'MP', 'BD', 'BR', 'DKV', 'TJKT', 'RPL'],
    ];

    public const array ROMAWI = [10 => 'X', 11 => 'XI', 12 => 'XII'];

    public function kelas(): HasMany
    {
        return $this->hasMany(ClassRoom::class, 'jurusan_id');
    }

    /** @return string[] */
    public static function allowedCodesForTingkat(int $tingkat): array
    {
        return self::ALLOWED_BY_TINGKAT[$tingkat] ?? [];
    }

    public static function isAllowedForTingkat(string $kode, int $tingkat): bool
    {
        // Tingkat di luar 10-12 (data lama) tidak dibatasi.
        if (! isset(self::ALLOWED_BY_TINGKAT[$tingkat])) {
            return true;
        }

        return in_array(strtoupper($kode), self::ALLOWED_BY_TINGKAT[$tingkat], true);
    }
}

<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Template kosong import jadwal: wali tinggal mengisi baris,
 * Aplikasi yang memvalidasi (lihat JadwalController@importPreview).
 */
class JadwalTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['kelas', 'hari', 'jam_mulai', 'jam_selesai', 'kode_mapel', 'email_guru'];
    }

    public function array(): array
    {
        return [
            ['XII RPL', 'senin', '08:00', '08:45', 'MTK', 'dn@sekolah.test'],
            ['XII RPL', 'senin', '08:45', '09:30', 'BING', 'mj@sekolah.test'],
        ];
    }
}

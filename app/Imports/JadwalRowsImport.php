<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/**
 * Penampung baris mentah satu sheet. Dipakai saat hanya butuh membaca
 * (pratinjau): validasi dan penulisan ditangani controller sendiri agar
 * tiap baris bisa dilaporkan error-nya satu per satu.
 */
class JadwalRowsImport implements ToArray
{
    /** @var list<list<mixed>> */
    public array $rows = [];

    public function array(array $array): void
    {
        $this->rows = $array;
    }
}

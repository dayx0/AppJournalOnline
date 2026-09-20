<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class AttendanceJournal extends Model
{
    protected $table = 'absensi_jurnal';
    protected $fillable = [
        'jurnal_id',
        'hadir',
        'izin',
        'sakit',
        'alpha',
    ];

    public function jurnal (): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'jurnal_id');
    }
}

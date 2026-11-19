<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $jurnal_id
 * @property int $hadir
 * @property int $izin
 * @property int $sakit
 * @property int $alpha
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Journal|null $jurnal
 */
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

    /** @return BelongsTo<Journal, $this> */
    public function jurnal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'jurnal_id');
    }
}

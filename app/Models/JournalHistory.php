<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $journal_id
 * @property int|null $actor_id
 * @property string $aksi
 * @property string|null $dari_status
 * @property string|null $ke_status
 * @property string|null $catatan
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Journal|null $journal
 * @property-read User|null $actor
 */
class JournalHistory extends Model
{
    public const AKSI_DIBUAT = 'dibuat';

    public const AKSI_DIVALIDASI = 'divalidasi';

    public const AKSI_REVISI = 'revisi';

    public const AKSI_RESET_PENDING = 'reset_pending';

    public const AKSI_DIISI = 'diisi';

    public const AKSI_DITOLAK = 'ditolak';

    public const AKSI_JAM_KOSONG = 'jam_kosong';

    public const AKSI_TERLAMBAT = 'terlambat';

    public const AKSI_IZIN = 'izin';

    protected $fillable = [
        'journal_id',
        'actor_id',
        'aksi',
        'dari_status',
        'ke_status',
        'catatan',
    ];

    /** @return BelongsTo<Journal, $this> */
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}

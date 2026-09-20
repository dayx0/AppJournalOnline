<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalHistory extends Model
{
    public const AKSI_DIBUAT = 'dibuat';

    public const AKSI_DIVALIDASI = 'divalidasi';

    public const AKSI_REVISI = 'revisi';

    public const AKSI_RESET_PENDING = 'reset_pending';

    protected $fillable = [
        'journal_id',
        'actor_id',
        'aksi',
        'dari_status',
        'ke_status',
        'catatan',
    ];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Journal extends Model
{
    protected $table = 'jurnal';

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $fillable = [
        'guru_id',
        'kelas_id',
        'mapel_id',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'materi',
        'kegiatan',
        'catatan',
        'status',
        'validated_by',
        'validated_at',
        'validation_note',
    ];

    protected function casts(): array
    {
        return ['validated_at' => 'datetime'];
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'kelas_id');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'mapel_id');
    }

    public function absensi(): HasOne
    {
        return $this->hasOne(AttendanceJournal::class, 'jurnal_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(JournalHistory::class, 'journal_id');
    }
}

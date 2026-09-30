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
        'status' => 'menunggu',
    ];

    protected $fillable = [
        'guru_id',
        'kelas_id',
        'jadwal_id',
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

    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_PENDING = 'pending';

    public const STATUS_DIVALIDASI = 'divalidasi';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_JAM_KOSONG = 'jam_kosong';

    public const STATUS_IZIN = 'izin';

    public const STATUS_TERLAMBAT = 'terlambat';

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class, 'jadwal_id');
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

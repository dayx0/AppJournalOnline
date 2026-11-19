<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jadwal extends Model
{
    protected $table = 'jadwal';

    public const HARI = [
        'senin' => 'Senin',
        'selasa' => 'Selasa',
        'rabu' => 'Rabu',
        'kamis' => 'Kamis',
        'jumat' => 'Jumat',
        'sabtu' => 'Sabtu',
    ];

    protected $fillable = [
        'kelas_id', 'mapel_id', 'guru_id', 'hari', 'jam_mulai', 'jam_selesai', 'semester', 'tahun_ajaran', 'aktif',
    ];

    protected static function booted(): void
    {
        // Jadwal baru selalu milik tahun ajaran aktif (mulai kosong tiap
        // tahun ajaran baru — jadwal lama dinonaktifkan, bukan dihapus).
        static::creating(function (Jadwal $jadwal) {
            if ($jadwal->tahun_ajaran === null) {
                $jadwal->tahun_ajaran = TahunAjaran::aktif()?->nama;
            }
        });
    }

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'kelas_id');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'mapel_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function jurnals(): HasMany
    {
        return $this->hasMany(Journal::class, 'jadwal_id');
    }
}

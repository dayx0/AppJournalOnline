<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $kelas_id
 * @property int $mapel_id
 * @property int|null $guru_id
 * @property string $hari
 * @property string $jam_mulai
 * @property string $jam_selesai
 * @property string|null $semester
 * @property string|null $tahun_ajaran
 * @property bool $aktif
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ClassRoom|null $kelas
 * @property-read Subject|null $mataPelajaran
 * @property-read User|null $guru
 * @property-read Collection<int, Journal> $jurnals
 */
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
        // tahun ajaran baru - jadwal lama dinonaktifkan, bukan dihapus).
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

    /** @return BelongsTo<ClassRoom, $this> */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'kelas_id');
    }

    /** @return BelongsTo<Subject, $this> */
    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'mapel_id');
    }

    /** @return BelongsTo<User, $this> */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    /** @return HasMany<Journal, $this> */
    public function jurnals(): HasMany
    {
        return $this->hasMany(Journal::class, 'jadwal_id');
    }
}

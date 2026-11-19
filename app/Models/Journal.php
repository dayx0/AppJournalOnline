<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $guru_id
 * @property int $kelas_id
 * @property int|null $jadwal_id
 * @property int $mapel_id
 * @property string $tanggal
 * @property string|null $tahun_ajaran
 * @property string|null $semester
 * @property string $jam_mulai
 * @property string $jam_selesai
 * @property string|null $materi
 * @property string|null $kegiatan
 * @property string|null $catatan
 * @property string $status
 * @property int|null $validated_by
 * @property Carbon|null $validated_at
 * @property string|null $validation_note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Jadwal|null $jadwal
 * @property-read User|null $guru
 * @property-read ClassRoom|null $kelas
 * @property-read Subject|null $mataPelajaran
 * @property-read AttendanceJournal|null $absensi
 * @property-read User|null $validator
 * @property-read Collection<int, JournalHistory> $histories
 */
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
        'tahun_ajaran',
        'semester',
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

    protected static function booted(): void
    {
        // Cap otomatis tahun ajaran+semester agar data baru tak pernah
        // tercampur dengan arsip. Mengikuti jadwal induk bila ada.
        static::creating(function (Journal $journal) {
            if ($journal->jadwal_id && $journal->tahun_ajaran === null) {
                $jadwal = Jadwal::find($journal->jadwal_id);
                $journal->tahun_ajaran = $jadwal?->tahun_ajaran;
            }

            if ($journal->tahun_ajaran === null && $journal->tanggal) {
                $journal->tahun_ajaran = TahunAjaran::namaUntukTanggal($journal->tanggal);
            }

            if ($journal->semester === null && $journal->tanggal) {
                $journal->semester = TahunAjaran::semesterUntukTanggal($journal->tanggal);
            }
        });
    }

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

    /** Arsip tahun ajaran lama = read-only total (guru, MPK, bahkan admin). */
    public function isTerkunci(): bool
    {
        return TahunAjaran::isTerkunci($this->tahun_ajaran, $this->semester);
    }

    /** @return BelongsTo<Jadwal, $this> */
    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class, 'jadwal_id');
    }

    /** @return BelongsTo<User, $this> */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
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

    /** @return HasOne<AttendanceJournal, $this> */
    public function absensi(): HasOne
    {
        return $this->hasOne(AttendanceJournal::class, 'jurnal_id');
    }

    /** @return BelongsTo<User, $this> */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /** @return HasMany<JournalHistory, $this> */
    public function histories(): HasMany
    {
        return $this->hasMany(JournalHistory::class, 'journal_id');
    }
}

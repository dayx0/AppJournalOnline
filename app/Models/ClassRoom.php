<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nama_kelas
 * @property int|null $jurusan_id
 * @property int|null $wali_kelas_id
 * @property int $tingkat
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Jurusan|null $jurusan
 * @property-read Collection<int, Journal> $jurnal
 * @property-read User|null $waliKelas
 * @property-read Collection<int, Jadwal> $jadwals
 * @property-read Collection<int, User> $mpks
 * @property-read Collection<int, Siswa> $siswa
 */
class ClassRoom extends Model
{
    protected $table = 'kelas';

    protected $fillable = ['nama_kelas', 'jurusan_id', 'wali_kelas_id', 'tingkat'];

    protected $casts = ['tingkat' => 'integer'];

    /** @return BelongsTo<Jurusan, $this> */
    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class, 'jurusan_id');
    }

    /** @return HasMany<Journal, $this> */
    public function jurnal(): HasMany
    {
        return $this->hasMany(Journal::class, 'kelas_id');
    }

    /** @return BelongsTo<User, $this> */
    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }

    /** @return HasMany<Jadwal, $this> */
    public function jadwals(): HasMany
    {
        return $this->hasMany(Jadwal::class, 'kelas_id');
    }

    /**
     * Semua akun MPK yang ditempatkan di kelas ini.
     *
     * @return HasMany<User, $this>
     */
    public function mpks(): HasMany
    {
        return $this->hasMany(User::class, 'kelas_id')->where('role', 'mpk');
    }

    /** @return HasMany<Siswa, $this> */
    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class, 'kelas_id');
    }
}

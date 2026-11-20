<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nama_mapel
 * @property string $kode_mapel
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Journal> $jurnal
 * @property-read Collection<int, Jadwal> $jadwals
 */
class Subject extends Model
{
    protected $table = 'mata_pelajaran';

    protected $fillable = ['nama_mapel', 'kode_mapel'];

    /** @return HasMany<Journal, $this> */
    public function jurnal(): HasMany
    {
        return $this->hasMany(Journal::class, 'mapel_id');
    }

    /** @return HasMany<Jadwal, $this> */
    public function jadwals(): HasMany
    {
        return $this->hasMany(Jadwal::class, 'mapel_id');
    }
}

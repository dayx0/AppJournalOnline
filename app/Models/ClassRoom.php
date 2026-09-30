<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassRoom extends Model
{
    protected $table = 'kelas';

    protected $fillable = ['nama_kelas', 'wali_kelas_id', 'jurusan', 'tingkat'];

    public function jurnal(): HasMany
    {
        return $this->hasMany(Journal::class, 'kelas_id');
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }

    public function jadwals(): HasMany
    {
        return $this->hasMany(Jadwal::class, 'kelas_id');
    }

    /**
     * Semua akun MPK yang ditempatkan di kelas ini.
     */
    public function mpks(): HasMany
    {
        return $this->hasMany(User::class, 'kelas_id')->where('role', 'mpk');
    }

    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class, 'kelas_id');
    }
}

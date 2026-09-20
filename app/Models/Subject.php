<?php

namespace App\Models;

use App\Models\Journal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $table = 'mata_pelajaran';
    protected $fillable = ['nama_mapel', 'kode_mapel'];
    public function jurnal(): HasMany
    {
        return $this->hasMany(Journal::class, 'mapel_id');
    }

}

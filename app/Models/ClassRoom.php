<?php

namespace App\Models;

use App\Models\Journal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassRoom extends Model
{
    protected $table = 'kelas';
    protected $fillable = ['nama_kelas', 'jurusan', 'tingkat'];
    public function jurnal(): HasMany
    {
        return $this->hasMany(Journal::class, 'kelas_id');
    } 
}

<?php

namespace App\Policies;

use App\Models\ClassRoom;
use App\Models\Jadwal;
use App\Models\User;

class JadwalPolicy
{
    /**
     * Melihat halaman jadwal: khusus admin dan guru yang menjadi wali
     * minimal satu kelas. Guru biasa melihat jadwal pribadinya lewat
     * halaman Jurnal, bukan di sini.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin()
            || ClassRoom::where('wali_kelas_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Jadwal $jadwal): bool
    {
        return $user->isWaliKelasOf($jadwal->kelas);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, ClassRoom $kelas): bool
    {
        return $user->isWaliKelasOf($kelas);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Jadwal $jadwal): bool
    {
        return $user->isWaliKelasOf($jadwal->kelas);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Jadwal $jadwal): bool
    {
        return $user->isWaliKelasOf($jadwal->kelas);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Jadwal $jadwal): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Jadwal $jadwal): bool
    {
        return false;
    }
}

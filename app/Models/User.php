<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $role
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $kelas_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'role', 'kelas_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function jurnal(): HasMany
    {
        return $this->hasMany(Journal::class, 'guru_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isGuru(): bool
    {
        return $this->role === 'guru';
    }

    public function isMpk(): bool
    {
        return $this->role === 'mpk';
    }

    /**
     * Kelas yang ditempati MPK ini. Null untuk admin/guru
     * dan untuk MPK yang belum ditempati.
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'kelas_id');
    }

    public function waliKelas(): HasMany
    {
        return $this->hasMany(ClassRoom::class, 'wali_kelas_id');
    }

    public function isWaliKelasOf(ClassRoom $kelas): bool
    {
        // Wali harus guru; admin tidak perlu lewat sini (di-bypass via Gate::before).
        return $this->isGuru() && (int) $kelas->wali_kelas_id === (int) $this->id;
    }

    /**
     * Apakah user ini boleh memvalidasi jurnal tersebut?
     * Syarat: role mpk + kelas jurnal sama dengan kelas MPK.
     */
    public function canValidateJournal(Journal $journal): bool
    {
        return $this->isMpk()
            && $this->kelas_id !== null
            && $journal->kelas_id === (int) $this->kelas_id;
    }

    /**
     * Profil siswa milik akun MPK (tabel siswa: NIS + cermin kelas).
     * Penempatan resmi tetap users.kelas_id (lihat canValidateJournal);
     * relasi ini untuk data profil + sinkronisasi admin.
     */
    public function siswa(): HasOne
    {
        return $this->hasOne(Siswa::class, 'user_id');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}

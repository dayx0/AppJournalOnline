<?php

namespace App\Models;
use App\Models\Journal;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'role'])]
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

    public function isAdmin(): bool {
        return $this->role === 'admin';
    }

    public function isGuru(): bool {
        return $this->role === 'guru';
    }

    public function isMpk(): bool {
        return $this->role === 'mpk';
    }

    /**
     * Profil siswa milik akun MPK (tabel siswa).
     */
    public function siswa(): HasOne {
        return $this->hasOne(Siswa::class, 'user_id');
    }

    /**
     * ID kelas yang dipegang MPK (via tabel siswa). Null bila bukan MPK
     * atau belum di-assign.
     */
    public function mpkKelasId(): ?int
    {
        $kelasId = $this->siswa?->kelas_id;

        return $kelasId === null ? null : (int) $kelasId;
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

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
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
 * @property string|null $kode_guru
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $role
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $kelas_id
 * @property bool $is_active
 * @property bool $must_change_password
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ClassRoom|null $kelas
 * @property-read Collection<int, ClassRoom> $waliKelas
 * @property-read Collection<int, Journal> $jurnal
 * @property-read Siswa|null $siswa
 */
#[Fillable(['name', 'kode_guru', 'email', 'password', 'role', 'kelas_id', 'is_active', 'must_change_password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected static function booted(): void
    {
        // Kode guru selalu mengikuti nama (huruf awal tiap kata).
        // Non-guru tidak punya kode (null).
        static::creating(function (User $user) {
            $user->kode_guru = $user->role === 'guru'
                ? self::generateKodeGuru((string) $user->name)
                : null;
        });

        static::updating(function (User $user) {
            if ($user->isDirty('name') || $user->isDirty('role')) {
                $user->kode_guru = $user->role === 'guru'
                    ? self::generateKodeGuru((string) $user->name)
                    : null;
            }
        });
    }

    /**
     * "Ana Himmatun Ulya" -> "AHU". Isi dalam kurung diabaikan
     * ("Ro (KK RPL)" -> "RO"). Satu kata pendek yang sudah berupa
     * kode ("AHU", "DS") dipakai utuh; satu kata panjang ("Sutrisno")
     * cukup huruf pertamanya ("S").
     */
    public static function generateKodeGuru(string $nama): string
    {
        $bersih = trim((string) preg_replace('/\([^)]*\)/', ' ', $nama));
        $kata = preg_split('/\s+/', $bersih, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $kata = array_values(array_filter(
            $kata,
            static fn (string $k): bool => preg_match('/[A-Za-z]/', $k) === 1,
        ));

        if (empty($kata)) {
            return '';
        }

        if (count($kata) === 1) {
            $huruf = (string) preg_replace('/[^A-Za-z]/', '', $kata[0]);
            if ($huruf === '') {
                return '';
            }

            return strlen($huruf) <= 4 ? strtoupper($huruf) : strtoupper($huruf[0]);
        }

        $kode = '';
        foreach ($kata as $k) {
            if (preg_match('/[A-Za-z]/', $k, $m)) {
                $kode .= strtoupper($m[0]);
            }
        }

        return $kode;
    }

    /** @return HasMany<Journal, $this> */
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
     *
     * @return BelongsTo<ClassRoom, $this>
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'kelas_id');
    }

    /** @return HasMany<ClassRoom, $this> */
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
     *
     * @return HasOne<Siswa, $this>
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
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    /** Akun nonaktif = sudah lulus/dinonaktifkan: tak boleh login. */
    public function isAktif(): bool
    {
        return (bool) $this->is_active;
    }
}

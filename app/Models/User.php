<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'username', 'external_id', 'password', 'role', 'is_active', 'must_change_password', 'password_changed_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // Akun panel admin CMS.
    public const CMS_ROLES = ['admin', 'editor'];

    // Akun LMS (dibuat superadmin / hasil sinkron Data Induk).
    public const LMS_ROLES = ['guru', 'siswa'];

    public const ROLES = ['superadmin', 'admin', 'editor', 'guru', 'siswa'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isCmsUser(): bool { return in_array($this->role, self::CMS_ROLES, true); }

    public function isAdmin(): bool { return $this->role === 'admin'; }

    public function isSuperadmin(): bool { return $this->role === 'superadmin'; }

    /** Halaman tujuan setelah login, sesuai role. */
    public function homeUrl(): string
    {
        return match ($this->role) {
            'superadmin' => '/superadmin',
            'guru' => '/ruang-guru',
            'siswa' => '/belajar',
            default => '/admin',
        };
    }
}

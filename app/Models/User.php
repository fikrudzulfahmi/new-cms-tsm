<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const CMS_ROLES = ['admin', 'editor'];
    public const APP_ROLES = ['superadmin', 'guru', 'siswa'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function isCmsUser(): bool { return in_array($this->role, self::CMS_ROLES, true); }

    /** Daftar plotting fitur: [['feature'=>'lms','role'=>'guru'], ...] */
    public function featureRoles(): array
    {
        return DB::table('feature_user')->where('user_id', $this->id)
            ->get(['feature', 'role'])->map(fn ($r) => ['feature' => $r->feature, 'role' => $r->role])->all();
    }

    public function hasFeature(string $feature, ?string $role = null): bool
    {
        return DB::table('feature_user')->where('user_id', $this->id)->where('feature', $feature)
            ->when($role, fn ($q) => $q->where('role', $role))->exists();
    }

    /** Halaman tujuan setelah login sesuai peran. */
    public function homeUrl(): string
    {
        if ($this->isCmsUser()) return '/admin';
        if ($this->role === 'superadmin') return '/superadmin';

        // Admin fitur yang hanya memegang satu fitur langsung masuk ke admin fitur tsb.
        $plots = collect($this->featureRoles());
        $features = $plots->pluck('feature')->unique();
        $adminOf = $plots->where('role', 'admin')->pluck('feature');
        if ($features->count() === 1 && $adminOf->count() === 1) {
            return '/app/' . $adminOf->first() . '/admin';
        }
        return '/portal';
    }
}

<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'nis', 'nis_lengkap', 'nisn', 'birth_date', 'gender', 'kelas', 'kelas_induk_id', 'induk_id', 'synced_at', 'niy', 'phone', 'address'])]
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
            'birth_date' => 'date:Y-m-d',
            'synced_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** "+62 812-3456-7890" / "62812..." / "0812 3456 7890" -> "081234567890"; selain nomor HP -> null. */
    public static function normalizePhone(?string $v): ?string
    {
        $d = preg_replace('/\D/', '', (string) $v);
        if (str_starts_with($d, '62')) {
            $d = '0' . substr($d, 2);
        } elseif (str_starts_with($d, '8')) {
            $d = '0' . $d;
        }

        return preg_match('/^08\d{8,12}$/', $d) ? $d : null;
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Catatan aktivitas panel admin. Ditulis oleh middleware LogAdminActivity
 * (perubahan data) dan AuthController (masuk/keluar/gagal login).
 *
 * Aturan: JANGAN menyimpan password, token, atau isi isian formulir mentah.
 */
class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'user_name', 'role', 'action', 'module', 'description',
        'subject_type', 'subject_id', 'method', 'path', 'status',
        'ip', 'user_agent', 'properties',
    ];

    protected $casts = [
        'properties' => 'array',
        'status' => 'integer',
    ];

    /** Label + warna lencana untuk tampilan admin. */
    public const LABELS = [
        'login' => ['Masuk', 'green'],
        'login_failed' => ['Login gagal', 'red'],
        'login_blocked' => ['Dibatasi', 'amber'],
        'login_disabled' => ['Akun nonaktif', 'amber'],
        'logout' => ['Keluar', 'slate'],
        'create' => ['Tambah', 'blue'],
        'update' => ['Ubah', 'amber'],
        'delete' => ['Hapus', 'red'],
        'denied' => ['Akses ditolak', 'purple'],
        'throttled' => ['Dibatasi', 'amber'],
        'error' => ['Error', 'red'],
    ];

    public const MODULES = [
        'posts' => 'Berita',
        'comments' => 'Komentar',
        'settings' => 'Pengaturan',
        'gallery' => 'Galeri',
        'teachers' => 'Guru',
        'categories' => 'Kategori',
        'competencies' => 'Kompetensi',
        'partners' => 'Mitra',
        'achievements' => 'Prestasi',
        'programs' => 'Program',
        'facilities' => 'Sarana',
        'sliders' => 'Slider',
        'testimonials' => 'Testimoni',
        'messages' => 'Pesan',
        'reports' => 'Laporan',
        'activity' => 'Log Aktivitas',
        'auth' => 'Login',
    ];

    public static function actionLabel(?string $action): string
    {
        return self::LABELS[$action][0] ?? ucfirst((string) $action);
    }

    public static function actionColor(?string $action): string
    {
        return self::LABELS[$action][1] ?? 'slate';
    }

    public static function moduleLabel(?string $module): string
    {
        return self::MODULES[$module] ?? ($module ? ucfirst($module) : '—');
    }

    /**
     * Tulis satu baris log. Tidak pernah melempar error: kegagalan mencatat
     * log tidak boleh menggagalkan permintaan pengguna.
     */
    public static function record(
        string $action,
        ?string $module = null,
        ?string $description = null,
        ?Model $subject = null,
        array $properties = [],
        ?Request $request = null,
    ): ?self {
        try {
            $request ??= request();
            $user = Auth::user();

            return static::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? ($properties['email'] ?? null),
                'role' => $user?->role,
                'action' => $action,
                'module' => $module,
                'description' => $description !== null ? mb_substr($description, 0, 1000) : null,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'method' => $request ? $request->method() : null,
                'path' => $request ? mb_substr($request->path(), 0, 255) : null,
                'status' => $properties['status'] ?? null,
                'ip' => $request?->ip(),
                'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
                'properties' => $properties ?: null,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    public function isSecurityEvent(): bool
    {
        return in_array($this->action, ['login_failed', 'login_blocked', 'login_disabled', 'denied', 'throttled', 'error'], true);
    }
}

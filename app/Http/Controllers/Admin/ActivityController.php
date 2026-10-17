<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Halaman Log Aktivitas: riwayat perubahan data admin + indikator gangguan
 * keamanan (login gagal berulang, akses ditolak, permintaan dibatasi).
 */
class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $tz = 'Asia/Jakarta';
        $action = (string) $request->query('aksi', '');
        $module = (string) $request->query('modul', '');
        $userId = (string) $request->query('user', '');
        $q = trim(mb_substr((string) $request->query('q', ''), 0, 100));
        $from = (string) $request->query('dari', '');
        $to = (string) $request->query('sampai', '');

        $query = ActivityLog::query();

        if ($action !== '' && array_key_exists($action, ActivityLog::LABELS)) {
            $action === 'security'
                ? $query->whereIn('action', ['login_failed', 'login_blocked', 'login_disabled', 'denied', 'throttled', 'error'])
                : $query->where('action', $action);
        }
        if ($module !== '' && array_key_exists($module, ActivityLog::MODULES)) {
            $query->where('module', $module);
        }
        if ($userId === 'sistem') {
            $query->whereNull('user_id');
        } elseif (ctype_digit($userId)) {
            $query->where('user_id', (int) $userId);
        }
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
            $query->where(function ($w) use ($like) {
                $w->where('description', 'like', $like)
                    ->orWhere('user_name', 'like', $like)
                    ->orWhere('ip', 'like', $like)
                    ->orWhere('path', 'like', $like);
            });
        }
        if ($from !== '' && strtotime($from) !== false) {
            $query->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($from)));
        }
        if ($to !== '' && strtotime($to) !== false) {
            $query->where('created_at', '<=', date('Y-m-d 23:59:59', strtotime($to)));
        }

        $logs = $query->latest('id')->paginate(30)->withQueryString()->through(fn (ActivityLog $l) => [
            'id' => $l->id,
            'at' => $l->created_at?->toIso8601String(),
            'at_wib' => $l->created_at?->copy()->setTimezone($tz)->format('d/m/Y H:i:s'),
            'user_name' => $l->user_name ?: 'Sistem',
            'role' => $l->role,
            'action' => $l->action,
            'action_label' => ActivityLog::actionLabel($l->action),
            'action_color' => ActivityLog::actionColor($l->action),
            'module' => $l->module,
            'module_label' => ActivityLog::moduleLabel($l->module),
            'description' => $l->description,
            'ip' => $l->ip,
            'path' => $l->path,
            'status' => $l->status,
            'changed' => $l->properties['changed'] ?? null,
            'message' => $l->properties['message'] ?? null,
            'is_security' => $l->isSecurityEvent(),
        ]);

        $since24 = now()->subDay();
        $stats = [
            'today' => ActivityLog::where('created_at', '>=', now($tz)->startOfDay()->utc())->count(),
            'failed_24h' => ActivityLog::where('action', 'login_failed')->where('created_at', '>=', $since24)->count(),
            'denied_24h' => ActivityLog::whereIn('action', ['denied', 'throttled'])->where('created_at', '>=', $since24)->count(),
            'users_7d' => ActivityLog::whereNotNull('user_id')->where('created_at', '>=', now()->subDays(7))->distinct('user_id')->count('user_id'),
        ];

        // Login gagal per IP: >= 5 dalam 24 jam dianggap percobaan pembobolan.
        $failedByIp = ActivityLog::query()
            ->where('action', 'login_failed')
            ->where('created_at', '>=', $since24)
            ->select('ip', DB::raw('COUNT(*) as attempts'), DB::raw('MAX(created_at) as last_at'), DB::raw('MIN(created_at) as first_at'), DB::raw('COUNT(DISTINCT user_name) as accounts'))
            ->groupBy('ip')
            ->orderByDesc('attempts')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'ip' => $r->ip ?: '—',
                'attempts' => (int) $r->attempts,
                'accounts' => (int) $r->accounts,
                'first_at' => $r->first_at ? \Illuminate\Support\Carbon::parse($r->first_at)->setTimezone($tz)->format('d/m H:i') : '—',
                'last_at' => $r->last_at ? \Illuminate\Support\Carbon::parse($r->last_at)->setTimezone($tz)->format('d/m H:i') : '—',
                'suspicious' => (int) $r->attempts >= 5,
            ]);

        $deniedRecent = ActivityLog::query()
            ->whereIn('action', ['denied', 'throttled'])
            ->where('created_at', '>=', now()->subDays(7))
            ->latest('id')
            ->limit(8)
            ->get(['id', 'created_at', 'user_name', 'description', 'ip', 'status'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'at' => $r->created_at?->copy()->setTimezone($tz)->format('d/m H:i'),
                'user_name' => $r->user_name ?: 'Tamu',
                'description' => $r->description,
                'ip' => $r->ip,
                'status' => $r->status,
            ]);

        return Inertia::render('Admin/Activity/Index', [
            'logs' => $logs,
            'stats' => $stats,
            'failedByIp' => $failedByIp,
            'deniedRecent' => $deniedRecent,
            'users' => User::orderBy('name')->get(['id', 'name', 'role']),
            'modules' => ActivityLog::MODULES,
            'actions' => collect(ActivityLog::LABELS)->map(fn ($v, $k) => ['value' => $k, 'label' => $v[0], 'color' => $v[1]])->values(),
            'filters' => compact('action', 'module', 'userId', 'q', 'from', 'to'),
            'retentionDays' => (int) config('activity.retention_days', 90),
        ]);
    }
}

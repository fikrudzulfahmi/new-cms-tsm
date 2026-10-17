<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Mencatat aktivitas panel admin: perubahan data (POST/PUT/PATCH/DELETE),
 * percobaan akses yang ditolak (401/403/419/429), dan error server.
 *
 * Dipasang paling depan di grup /admin supaya penolakan oleh middleware
 * auth/admin/role ikut tercatat.
 */
class LogAdminActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
            $this->record($request, $status);
            throw $e;
        }

        $this->record($request, $response->getStatusCode());

        return $response;
    }

    private function record(Request $request, int $status): void
    {
        try {
            $mutating = in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
            $denied = in_array($status, [401, 403, 419, 429], true);
            $failed = $status >= 500;

            // Permintaan baca yang berhasil tidak dicatat (hanya akan membanjiri tabel).
            if (! $mutating && ! $denied && ! $failed) {
                return;
            }

            $route = $request->route();
            $name = (string) ($route?->getName() ?? '');
            $parts = explode('.', ltrim($name, '.'));
            $module = $parts[1] ?? null;
            $verb = $parts[2] ?? null;
            $method = strtoupper($request->method());

            $action = match (true) {
                in_array($status, [401, 403, 419], true) => 'denied',
                $status === 429 => 'throttled',
                $failed => 'error',
                $method === 'DELETE' => 'delete',
                in_array($method, ['PUT', 'PATCH'], true) => 'update',
                default => 'create',
            };

            $verbLabel = match ($verb) {
                'approve' => 'disetujui',
                'reject' => 'ditolak',
                'spam' => 'ditandai spam',
                'read' => 'ditandai dibaca',
                'upload' => 'unggah gambar',
                'store' => 'tambah',
                default => ActivityLog::actionLabel($action) === 'Ubah' ? 'ubah' : mb_strtolower(ActivityLog::actionLabel($action)),
            };

            $subject = null;
            foreach ((array) $route?->parameters() as $param) {
                if ($param instanceof Model) {
                    $subject = $param;
                    break;
                }
            }

            $title = $subject?->getAttribute('title')
                ?? $subject?->getAttribute('name')
                ?? $subject?->getAttribute('subject_text')
                ?? null;

            $moduleLabel = ActivityLog::moduleLabel($module);
            $description = $action === 'denied'
                ? 'Percobaan akses ditolak: ' . $method . ' /' . $request->path()
                : trim($moduleLabel . ' — ' . ucfirst($verbLabel) . ($title ? ': ' . mb_substr((string) $title, 0, 120) : ''));

            $flash = session('success') ?: session('error');
            $changed = session('changed_settings');

            $properties = array_filter([
                'status' => $status,
                'route' => $name ?: null,
                'message' => is_string($flash) ? mb_substr($flash, 0, 300) : null,
                'changed' => is_array($changed) ? array_slice($changed, 0, 30) : null,
            ], static fn ($v) => $v !== null && $v !== []);

            ActivityLog::record($action, $module, $description, $subject, $properties, $request);
        } catch (\Throwable $e) {
            report($e); // pencatatan log tidak boleh menggagalkan permintaan
        }
    }
}

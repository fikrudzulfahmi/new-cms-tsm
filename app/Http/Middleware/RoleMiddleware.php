<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi route ke role tertentu. Dipakai untuk memisahkan hak akses
 * admin (semua menu) dan editor (berita + komentar saja).
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403, 'Akun Anda tidak punya hak akses ke halaman ini.');
        }

        return $next($request);
    }
}

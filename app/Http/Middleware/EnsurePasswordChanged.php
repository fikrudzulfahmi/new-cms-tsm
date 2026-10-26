<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun dengan password awal (sama dengan username) tidak boleh memakai
 * fitur apa pun sebelum mengganti password.
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Akun yang dinonaktifkan kehilangan sesinya saat itu juga.
        if ($user && ! $user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['login' => 'Akun Anda dinonaktifkan.']);
        }

        if ($user && $user->must_change_password
            && ! $request->routeIs('password.change', 'password.update', 'logout')) {
            if ($request->expectsJson() && ! $request->header('X-Inertia')) {
                abort(403, 'Ganti password terlebih dahulu.');
            }

            return redirect()->route('password.change');
        }

        return $next($request);
    }
}

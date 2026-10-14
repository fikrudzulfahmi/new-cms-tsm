<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }
        if (!in_array($request->user()->role, ['admin', 'editor'])) {
            abort(403, 'Akses ditolak');
        }
        if (!$request->user()->is_active) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Akun Anda dinonaktifkan.');
        }
        return $next($request);
    }
}

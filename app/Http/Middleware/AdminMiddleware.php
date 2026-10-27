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
        $user = $request->user();
        if ($user->isContributor()) {
            // Penulis tamu: hanya modul berita (+ upload gambar editor).
            if (! $request->is('admin/berita*', 'admin/pengaturan/upload')) {
                return $request->is('admin') ? redirect('/admin/berita') : abort(403, 'Anda hanya bisa mengelola berita.');
            }

            return $next($request);
        }
        if (!$request->user()->isCmsUser()) {
            abort(403, 'Akun Anda tidak punya akses ke Admin CMS.');
        }
        if (!$request->user()->is_active) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Akun Anda dinonaktifkan.');
        }
        return $next($request);
    }
}

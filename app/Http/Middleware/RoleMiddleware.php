<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Pemakaian: ->middleware('role:superadmin') atau 'role:guru,siswa'
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('masuk');
        }
        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('masuk')->withErrors(['email' => 'Akun Anda dinonaktifkan.']);
        }
        if ($roles && !in_array($user->role, $roles, true)) {
            return redirect($user->homeUrl())->with('error', 'Anda tidak punya akses ke halaman tersebut.');
        }
        return $next($request);
    }
}

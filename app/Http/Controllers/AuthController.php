<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

// Dua pintu masuk: /login (Admin CMS) dan /masuk (TSM Superapp). Setelah login
// semua user diarahkan sesuai perannya (User::homeUrl), apa pun pintunya.
class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) return redirect(Auth::user()->homeUrl());
        return Inertia::render('Auth/Login', ['mode' => 'cms']);
    }

    public function showMasuk()
    {
        if (Auth::check()) return redirect(Auth::user()->homeUrl());
        return Inertia::render('Auth/Login', ['mode' => 'app']);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'Akun Anda dinonaktifkan.']);
            }
            $user->forceFill(['last_login_at' => now()])->save();
            $request->session()->regenerate();

            // "intended" hanya dipakai jika masih sesuai peran (mis. admin CMS tidak dilempar ke /superadmin)
            $home = $user->homeUrl();
            $intended = $request->session()->pull('url.intended');
            $path = $intended ? (parse_url($intended, PHP_URL_PATH) ?: '/') : null;
            $prefix = '/' . explode('/', trim($home, '/'))[0];
            if ($path && str_starts_with($path, $prefix)) return redirect($intended);
            return redirect($home);
        }

        return back()->withErrors(['email' => 'Email atau password salah.'])->withInput($request->only('email', 'remember'));
    }

    public function logout(Request $request)
    {
        $wasCms = $request->user()?->isCmsUser();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route($wasCms ? 'login' : 'masuk');
    }
}

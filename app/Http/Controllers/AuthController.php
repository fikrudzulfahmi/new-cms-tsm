<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;

// Panel Admin CMS: satu pintu masuk di /login.
class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) return redirect(Auth::user()->homeUrl());
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $d = $request->validate([
            'email' => 'required|email|max:150',
            'password' => 'required|string',
        ], ['email.required' => 'Isi email.', 'email.email' => 'Format email tidak valid.']);

        $key = 'login:' . $request->ip() . ':' . strtolower($d['email']);
        $ipKey = 'login-ip:' . $request->ip();

        // Batas per akun (cegah tebak password satu email) + batas per IP
        // (cegah credential stuffing ke banyak email dari satu mesin).
        if (RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts($ipKey, 30)) {
            $wait = RateLimiter::tooManyAttempts($ipKey, 30) ? RateLimiter::availableIn($ipKey) : RateLimiter::availableIn($key);
            ActivityLog::record('login_blocked', 'auth', 'Login dibatasi setelah terlalu banyak percobaan', null, [
                'email' => strtolower((string) $d['email']),
                'wait_seconds' => $wait,
                'status' => 429,
            ]);
            return back()->withErrors(['email' => 'Terlalu banyak percobaan. Coba lagi dalam ' . $wait . ' detik.'])
                ->withInput($request->only('email', 'remember'));
        }

        if (Auth::attempt(['email' => strtolower($d['email']), 'password' => $d['password']], $request->boolean('remember'))) {
            $user = Auth::user();
            if (!$user->is_active) {
                ActivityLog::record('login_disabled', 'auth', 'Login ditolak: akun dinonaktifkan', $user, [
                    'email' => $user->email,
                    'status' => 403,
                ]);
                Auth::logout();
                return back()->withErrors(['email' => 'Akun Anda dinonaktifkan.']);
            }
            RateLimiter::clear($key);
            RateLimiter::clear($ipKey);
            $user->forceFill(['last_login_at' => now()])->save();
            $request->session()->regenerate();

            ActivityLog::record('login', 'auth', 'Masuk ke panel admin', $user, ['status' => 200]);

            return redirect($request->session()->pull('url.intended') ?: $user->homeUrl());
        }

        RateLimiter::hit($key, 300);
        RateLimiter::hit($ipKey, 600);
        ActivityLog::record('login_failed', 'auth', 'Login gagal: email atau password salah', null, [
            'email' => strtolower((string) $d['email']),
            'status' => 401,
        ]);
        return back()->withErrors(['email' => 'Email atau password salah.'])->withInput($request->only('email', 'remember'));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        ActivityLog::record('logout', 'auth', 'Keluar dari panel admin', null, ['status' => 302]);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}

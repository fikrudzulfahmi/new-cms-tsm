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
        // Field 'login' berisi email (admin/editor) atau username (guru/siswa).
        // 'email' tetap diterima agar klien lama tidak rusak.
        $request->merge(['login' => trim((string) $request->input('login', $request->input('email')))]);
        $d = $request->validate([
            'login' => 'required|string|max:150',
            'password' => 'required|string|max:200',
        ], ['login.required' => 'Isi email atau username.']);
        $d['email'] = mb_strtolower($d['login']);
        $field = str_contains($d['email'], '@') ? 'email' : 'username';
        // NIS/NIK (username) tidak boleh masuk activity log.
        $logId = $field === 'email' ? $d['email'] : '[username]';

        $key = 'login:' . $request->ip() . ':' . strtolower($d['email']);
        $ipKey = 'login-ip:' . $request->ip();

        // Batas per akun (cegah tebak password satu email) + batas per IP
        // (cegah credential stuffing ke banyak email dari satu mesin).
        if (RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts($ipKey, 30)) {
            $wait = RateLimiter::tooManyAttempts($ipKey, 30) ? RateLimiter::availableIn($ipKey) : RateLimiter::availableIn($key);
            ActivityLog::record('login_blocked', 'auth', 'Login dibatasi setelah terlalu banyak percobaan', null, [
                'email' => $logId,
                'wait_seconds' => $wait,
                'status' => 429,
            ]);
            return back()->withErrors(['login' => 'Terlalu banyak percobaan. Coba lagi dalam ' . $wait . ' detik.'])
                ->withInput($request->only('login', 'remember'));
        }

        if (Auth::attempt([$field => $d['email'], 'password' => $d['password']], $request->boolean('remember'))) {
            $user = Auth::user();
            if (!$user->is_active) {
                ActivityLog::record('login_disabled', 'auth', 'Login ditolak: akun dinonaktifkan', $user, [
                    'email' => $user->email ?? '[username]',
                    'status' => 403,
                ]);
                Auth::logout();
                return back()->withErrors(['login' => 'Akun Anda dinonaktifkan.']);
            }
            RateLimiter::clear($key);
            RateLimiter::clear($ipKey);
            $user->forceFill(['last_login_at' => now()])->save();
            $request->session()->regenerate();

            ActivityLog::record('login', 'auth', 'Masuk ke aplikasi', $user, ['status' => 200]);

            $intended = (string) $request->session()->pull('url.intended', '');
            $path = parse_url($intended, PHP_URL_PATH) ?: '';
            $host = parse_url($intended, PHP_URL_HOST);
            $aman = $intended !== ''
                && ($host === null || $host === $request->getHost())
                && str_starts_with($path, '/') && ! str_starts_with($path, '//')
                && (! str_starts_with($path, '/admin') || $user->isCmsUser());

            return redirect($aman ? $intended : $user->homeUrl());
        }

        RateLimiter::hit($key, 300);
        RateLimiter::hit($ipKey, 600);
        ActivityLog::record('login_failed', 'auth', 'Login gagal: akun atau password salah', null, [
            'email' => $logId,
            'status' => 401,
        ]);
        return back()->withErrors(['login' => 'Email/username atau password salah.'])->withInput($request->only('login', 'remember'));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        ActivityLog::record('logout', 'auth', 'Keluar dari aplikasi', null, ['status' => 302]);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}

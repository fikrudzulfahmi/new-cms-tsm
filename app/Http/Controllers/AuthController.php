<?php

namespace App\Http\Controllers;

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
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['email' => 'Terlalu banyak percobaan. Coba lagi dalam ' . RateLimiter::availableIn($key) . ' detik.'])
                ->withInput($request->only('email', 'remember'));
        }

        if (Auth::attempt(['email' => strtolower($d['email']), 'password' => $d['password']], $request->boolean('remember'))) {
            $user = Auth::user();
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'Akun Anda dinonaktifkan.']);
            }
            RateLimiter::clear($key);
            $user->forceFill(['last_login_at' => now()])->save();
            $request->session()->regenerate();

            return redirect($request->session()->pull('url.intended') ?: $user->homeUrl());
        }

        RateLimiter::hit($key, 300);
        return back()->withErrors(['email' => 'Email atau password salah.'])->withInput($request->only('email', 'remember'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}

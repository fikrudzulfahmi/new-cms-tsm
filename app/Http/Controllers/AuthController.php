<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
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
        return Inertia::render('Auth/Login', ['mode' => 'app', 'recaptchaKey' => config('services.recaptcha.site_key')]);
    }

    /**
     * Login siswa: NIS + tanggal lahir. NIS boleh lengkap, atau 5 angka pertama hasil scan kartu QR
     * (dicocokkan sebagai awalan; tanggal lahir jadi pembeda bila ada lebih dari satu).
     */
    public function loginSiswa(Request $request)
    {
        $d = $request->validate([
            'nis' => ['required', 'string', 'regex:/^\d{5,20}$/'],
            'birth_date' => 'required|date_format:Y-m-d',
        ], ['nis.regex' => 'NIS hanya angka (minimal 5 digit).']);

        $key = 'siswa-login:' . $request->ip() . ':' . $d['nis'];
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['nis' => 'Terlalu banyak percobaan. Coba lagi dalam ' . RateLimiter::availableIn($key) . ' detik.']);
        }
        if (!$this->recaptchaOk($request)) {
            return back()->withErrors(['captcha' => 'Verifikasi reCAPTCHA gagal, silakan centang ulang.']);
        }

        $q = User::where('role', 'siswa')->whereDate('birth_date', $d['birth_date']);
        $exact = (clone $q)->where('nis', $d['nis'])->get();
        $found = $exact->isNotEmpty() ? $exact
            : (strlen($d['nis']) === 5 ? (clone $q)->where('nis', 'like', $d['nis'] . '%')->limit(2)->get() : collect());

        if ($found->count() !== 1) {
            RateLimiter::hit($key, 300);
            return back()->withErrors(['nis' => $found->count() > 1
                ? 'Data cocok dengan lebih dari satu siswa. Masukkan NIS lengkap.'
                : 'NIS atau tanggal lahir tidak cocok.'])->withInput($request->only('nis'));
        }
        $user = $found->first();
        if (!$user->is_active) return back()->withErrors(['nis' => 'Akun Anda dinonaktifkan. Hubungi wali kelas/superadmin.']);

        RateLimiter::clear($key);
        Auth::login($user);
        $user->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();
        return redirect($user->homeUrl());
    }

    private function recaptchaOk(Request $request): bool
    {
        $secret = config('services.recaptcha.secret');
        if (!$secret || !config('services.recaptcha.site_key')) return true; // captcha belum diatur
        $token = (string) $request->input('captcha');
        if ($token === '') return false;
        try {
            return (bool) Http::asForm()->timeout(8)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret, 'response' => $token, 'remoteip' => $request->ip(),
            ])->json('success');
        } catch (\Throwable) {
            return false;
        }
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

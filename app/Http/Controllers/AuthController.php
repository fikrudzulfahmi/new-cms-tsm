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
            'nis' => ['required', 'string', 'regex:/^[0-9][0-9\/\-\.\s]{3,29}$/'],
            'birth_date' => 'required|date_format:Y-m-d',
        ], ['nis.regex' => 'NIS harus angka (boleh memuat tanda / seperti di kartu), minimal 5 karakter.']);

        $key = 'siswa-login:' . $request->ip() . ':' . $d['nis'];
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['nis' => 'Terlalu banyak percobaan. Coba lagi dalam ' . RateLimiter::availableIn($key) . ' detik.']);
        }
        if (!$this->recaptchaOk($request)) {
            return back()->withErrors(['captcha' => 'Verifikasi reCAPTCHA gagal, silakan centang ulang.']);
        }

        // NIS di kartu siswa = 5 angka pertama. Kalau siswa mengetik NIS lengkap (31577/1582/004),
        // yang dicocokkan tetap 5 angka pertamanya.
        $angka = preg_replace('/\D/', '', $d['nis']);
        $kunci = strlen($angka) >= 5 ? substr($angka, 0, 5) : $angka;

        $q = User::where('role', 'siswa')->whereDate('birth_date', $d['birth_date']);
        $found = $kunci === '' ? collect() : (clone $q)->where('nis', $kunci)->get();
        if ($found->isEmpty() && strlen($angka) >= 5) {
            // cadangan untuk data yang NIS-nya masih tersimpan utuh (315771582004)
            $found = (clone $q)->where(fn ($w) => $w->where('nis', 'like', $angka . '%')->orWhere('nis_lengkap', $d['nis']))->limit(2)->get();
        }

        if ($found->count() !== 1) {
            RateLimiter::hit($key, 300);
            return back()->withErrors(['nis' => $found->count() > 1
                ? 'Data cocok dengan lebih dari satu siswa. Hubungi wali kelas/superadmin.'
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

    /**
     * Login email + password. Di /masuk (Superapp) kolom "email" juga menerima no HP,
     * supaya guru bisa masuk dengan email atau nomor HP yang didaftarkan superadmin.
     */
    public function login(Request $request)
    {
        $isApp = $request->is('masuk');
        $d = $request->validate([
            'email' => ['required', 'string', 'max:150'],
            'password' => 'required|string',
        ], ['email.required' => $isApp ? 'Isi email atau no HP.' : 'Isi email.']);

        $login = trim($d['email']);
        $phone = $isApp && !str_contains($login, '@') ? User::normalizePhone($login) : null;
        if (!$phone && !filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return back()->withErrors(['email' => $isApp ? 'Masukkan email atau no HP yang valid.' : 'Format email tidak valid.'])->withInput($request->only('email', 'remember'));
        }

        $key = 'login:' . $request->ip() . ':' . strtolower($login);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['email' => 'Terlalu banyak percobaan. Coba lagi dalam ' . RateLimiter::availableIn($key) . ' detik.'])->withInput($request->only('email', 'remember'));
        }

        $credentials = $phone ? ['phone' => $phone, 'password' => $d['password']] : ['email' => strtolower($login), 'password' => $d['password']];
        // siswa tidak memakai password; login siswa lewat /masuk/siswa (NIS + tanggal lahir)
        $credentials[] = fn ($q) => $q->where('role', '!=', 'siswa');

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'Akun Anda dinonaktifkan. Hubungi superadmin.']);
            }
            RateLimiter::clear($key);
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

        RateLimiter::hit($key, 300);
        return back()->withErrors(['email' => $isApp ? 'Email/no HP atau password salah.' : 'Email atau password salah.'])->withInput($request->only('email', 'remember'));
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

<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;

class PasswordController extends Controller
{
    public function edit(Request $request)
    {
        return Inertia::render('Auth/ChangePassword', [
            'forced' => (bool) $request->user()->must_change_password,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $key = 'pwchange:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['current_password' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.']);
        }

        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|max:100|confirmed',
        ], [
            'current_password.required' => 'Isi password saat ini.',
            'password.required' => 'Isi password baru.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            RateLimiter::hit($key, 300);

            return back()->withErrors(['current_password' => 'Password saat ini salah.']);
        }

        if ($data['password'] === $data['current_password']) {
            return back()->withErrors(['password' => 'Password baru tidak boleh sama dengan password lama.']);
        }

        $lower = mb_strtolower($data['password']);
        if (($user->username && $lower === mb_strtolower($user->username))
            || ($user->email && $lower === mb_strtolower($user->email))) {
            return back()->withErrors(['password' => 'Password baru tidak boleh sama dengan username atau email.']);
        }

        $user->forceFill([
            'password' => $data['password'],
            'must_change_password' => false,
            'password_changed_at' => now(),
            'remember_token' => \Illuminate\Support\Str::random(60),
        ])->save();

        RateLimiter::clear($key);
        $request->session()->regenerate();

        // Hanya mencatat kejadian, tanpa password.
        ActivityLog::record('update', 'auth', 'Mengganti password akun', $user, ['status' => 200]);

        return redirect($user->homeUrl())->with('success', 'Password berhasil diganti.');
    }
}

<?php

namespace App\Http\Controllers\Superapp;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

// Superadmin > Admin Fitur: superadmin membuat AKUN KHUSUS admin per fitur (role "staf", bukan guru).
// Anggota fitur (guru/pembimbing/penguji/siswa/pembaca) ditambahkan oleh admin fitur itu sendiri.
class FeatureAdminController extends Controller
{
    private function features(): array { return config('superapp.features'); }

    public function index()
    {
        $features = $this->features();
        $plots = DB::table('feature_user')->join('users', 'users.id', '=', 'feature_user.user_id')
            ->where('feature_user.role', 'admin')
            ->orderBy('users.name')
            ->get(['feature_user.feature', 'users.id', 'users.name', 'users.email', 'users.phone', 'users.role', 'users.is_active', 'users.last_login_at'])
            ->groupBy('feature');
        $members = DB::table('feature_user')->where('role', '!=', 'admin')
            ->selectRaw('feature, count(distinct user_id) c')->groupBy('feature')->pluck('c', 'feature');

        $list = collect($features)->map(fn ($f, $key) => [
            'key' => $key, 'name' => $f['name'], 'full' => $f['full'], 'icon' => $f['icon'],
            'adminLabel' => $f['roles']['admin']['label'],
            'admins' => ($plots[$key] ?? collect())->values(),
            'members' => $members[$key] ?? 0,
        ])->values();

        return Inertia::render('Superadmin/FeatureAdmins', ['features' => $list]);
    }

    public function store(Request $request)
    {
        $request->merge(['phone' => User::normalizePhone((string) $request->input('phone')) ?: $request->input('phone')]);
        $d = $request->validate([
            'feature' => ['required', Rule::in(array_keys($this->features()))],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'regex:/^08\d{8,12}$/', Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'min:8', 'max:100'],
        ], [
            'name.required' => 'Isi nama akun admin.',
            'password.required' => 'Isi password akun admin (min. 8 karakter).',
            'phone.regex' => 'No HP tidak valid (contoh: 081234567890).',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'phone.unique' => 'No HP ini sudah dipakai akun lain.',
        ]);
        $f = $this->features()[$d['feature']];

        if (!filled($d['email'] ?? null) && !filled($d['phone'] ?? null)) {
            return back()->withErrors(['email' => 'Isi minimal salah satu: email atau no HP (dipakai untuk login).']);
        }
        $user = User::create([
            'name' => $d['name'], 'email' => filled($d['email'] ?? null) ? strtolower($d['email']) : null,
            'phone' => $d['phone'] ?? null, 'password' => $d['password'], 'role' => 'staf', 'is_active' => true,
        ]);

        DB::table('feature_user')->insertOrIgnore([
            'user_id' => $user->id, 'feature' => $d['feature'], 'role' => 'admin', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', "{$user->name} sekarang {$f['roles']['admin']['label']}. Login di /masuk dengan " . ($user->email ?: $user->phone) . '.');
    }

    public function destroy(Request $request, string $feature, User $user)
    {
        $f = $this->features()[$feature] ?? abort(404);
        DB::table('feature_user')->where(['user_id' => $user->id, 'feature' => $feature, 'role' => 'admin'])->delete();

        // akun khusus admin dihapus; data guru (bila ada sisa penunjukan lama) tidak ikut terhapus
        if ($user->role === 'staf' && !DB::table('feature_user')->where('user_id', $user->id)->exists()) {
            $user->delete();
        }

        return back()->with('success', "Akun admin {$user->name} dihapus dari {$f['roles']['admin']['label']}.");
    }

    /** Ubah akun khusus admin: nama, email, no HP, password, status. */
    public function updateStaf(Request $request, User $user)
    {
        abort_unless($user->role === 'staf', 404);
        if ($request->has('phone')) {
            $request->merge(['phone' => User::normalizePhone((string) $request->input('phone')) ?: $request->input('phone')]);
        }
        $d = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'email' => ['sometimes', 'nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['sometimes', 'nullable', 'regex:/^08\d{8,12}$/', Rule::unique('users', 'phone')->ignore($user->id)],
            'password' => 'nullable|string|min:8|max:100',
            'is_active' => 'boolean',
        ], [
            'phone.regex' => 'No HP tidak valid (contoh: 081234567890).',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'phone.unique' => 'No HP ini sudah dipakai akun lain.',
        ]);
        if ($request->has('email') || $request->has('phone')) {
            $email = array_key_exists('email', $d) ? $d['email'] : $user->email;
            $phone = array_key_exists('phone', $d) ? $d['phone'] : $user->phone;
            if (!filled($email) && !filled($phone)) {
                return back()->withErrors(['email' => 'Isi minimal salah satu: email atau no HP (dipakai untuk login).']);
            }
            $user->email = filled($email) ? strtolower($email) : null;
            $user->phone = filled($phone) ? $phone : null;
        }
        if (isset($d['name'])) $user->name = $d['name'];
        if (filled($d['password'] ?? null)) $user->password = $d['password'];
        $user->is_active = $d['is_active'] ?? $user->is_active;
        $user->save();

        return back()->with('success', "Akun {$user->name} diperbarui.");
    }
}

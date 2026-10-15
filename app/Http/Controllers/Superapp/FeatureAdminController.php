<?php

namespace App\Http\Controllers\Superapp;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

// Superadmin > Admin Fitur: superadmin HANYA menunjuk admin per fitur (LMS, PKL, UKK, Perpus).
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

        return Inertia::render('Superadmin/FeatureAdmins', [
            'features' => $list,
            'teachers' => User::whereIn('role', ['guru', 'staf'])->where('is_active', true)->orderBy('name')
                ->get(['id', 'name', 'email', 'phone', 'role']),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge(['phone' => User::normalizePhone((string) $request->input('phone')) ?: $request->input('phone')]);
        $d = $request->validate([
            'feature' => ['required', Rule::in(array_keys($this->features()))],
            'mode' => ['required', Rule::in(['existing', 'new'])],
            'user_id' => ['required_if:mode,existing', 'nullable', 'integer'],
            'name' => ['required_if:mode,new', 'nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'regex:/^08\d{8,12}$/', Rule::unique('users', 'phone')],
            'password' => ['required_if:mode,new', 'nullable', 'string', 'min:8', 'max:100'],
        ], [
            'user_id.required_if' => 'Pilih guru yang dijadikan admin.',
            'name.required_if' => 'Isi nama akun admin.',
            'password.required_if' => 'Isi password akun admin (min. 8 karakter).',
            'phone.regex' => 'No HP tidak valid (contoh: 081234567890).',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'phone.unique' => 'No HP ini sudah dipakai akun lain.',
        ]);
        $f = $this->features()[$d['feature']];

        if ($d['mode'] === 'existing') {
            $user = User::whereIn('role', ['guru', 'staf'])->find($d['user_id']);
            if (!$user) return back()->withErrors(['user_id' => 'Guru tidak ditemukan.']);
        } else {
            if (!filled($d['email'] ?? null) && !filled($d['phone'] ?? null)) {
                return back()->withErrors(['email' => 'Isi minimal salah satu: email atau no HP (dipakai untuk login).']);
            }
            // akun khusus admin fitur (bukan guru) -> peran "staf"
            $user = User::create([
                'name' => $d['name'], 'email' => filled($d['email'] ?? null) ? strtolower($d['email']) : null,
                'phone' => $d['phone'] ?? null, 'password' => $d['password'], 'role' => 'staf', 'is_active' => true,
            ]);
        }

        DB::table('feature_user')->insertOrIgnore([
            'user_id' => $user->id, 'feature' => $d['feature'], 'role' => 'admin', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', "{$user->name} sekarang {$f['roles']['admin']['label']}. Login di /masuk dengan " . ($user->email ?: $user->phone) . '.');
    }

    public function destroy(Request $request, string $feature, User $user)
    {
        $f = $this->features()[$feature] ?? abort(404);
        DB::table('feature_user')->where(['user_id' => $user->id, 'feature' => $feature, 'role' => 'admin'])->delete();

        // akun staf (khusus admin) yang sudah tidak memegang fitur apa pun dihapus
        if ($user->role === 'staf' && !DB::table('feature_user')->where('user_id', $user->id)->exists()) {
            $user->delete();
            return back()->with('success', "{$user->name} dicabut dari {$f['roles']['admin']['label']}; akun adminnya dihapus karena tidak memegang fitur lain.");
        }

        return back()->with('success', "{$user->name} dicabut dari {$f['roles']['admin']['label']}.");
    }

    /** Ganti password / status akun staf (akun guru diubah lewat Data Guru). */
    public function updateStaf(Request $request, User $user)
    {
        abort_unless($user->role === 'staf', 404);
        $d = $request->validate(['password' => 'nullable|string|min:8|max:100', 'is_active' => 'boolean']);
        if (filled($d['password'] ?? null)) $user->password = $d['password'];
        $user->is_active = $d['is_active'] ?? $user->is_active;
        $user->save();

        return back()->with('success', "Akun {$user->name} diperbarui.");
    }
}

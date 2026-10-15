<?php

namespace App\Http\Controllers\Superapp;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

// Superadmin > Data Guru: input manual. Guru login di /masuk memakai email atau no HP + password.
class TeacherController extends Controller
{
    private const FIELDS = ['id', 'name', 'niy', 'gender', 'phone', 'email', 'address', 'is_active', 'last_login_at'];

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        // cari no HP tanpa peduli spasi/strip/+62: "0812-9999" atau "62812" -> "08129999" / "0812"
        $digits = preg_replace('/\D/', '', $q);
        $phoneQ = str_starts_with($digits, '62') ? '0' . substr($digits, 2) : $digits;
        $teachers = User::where('role', 'guru')
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%$q%")
                ->orWhere('niy', 'like', "$q%")->orWhere('email', 'like', "%$q%")->when(strlen($phoneQ) >= 4, fn ($y) => $y->orWhere('phone', 'like', "%$phoneQ%"))))
            ->orderBy('name')
            ->paginate(30, self::FIELDS)
            ->withQueryString();

        return Inertia::render('Superadmin/Teachers', [
            'teachers' => $teachers,
            'filters' => ['q' => $q],
        ]);
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $request->merge(['phone' => User::normalizePhone((string) $request->input('phone')) ?: $request->input('phone')]);

        $d = $request->validate([
            'name' => 'required|string|max:150',
            'niy' => ['nullable', 'string', 'max:30', Rule::unique('users', 'niy')->ignore($user?->id)],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'phone' => ['required_without:email', 'nullable', 'regex:/^08\d{8,12}$/', Rule::unique('users', 'phone')->ignore($user?->id)],
            'email' => ['required_without:phone', 'nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user?->id)],
            'address' => 'nullable|string|max:500',
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'max:100'],
            'is_active' => 'boolean',
        ], [
            'phone.regex' => 'No HP tidak valid (contoh: 081234567890).',
            'phone.unique' => 'No HP ini sudah dipakai akun lain.',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'niy.unique' => 'NIY ini sudah dipakai guru lain.',
            'phone.required_without' => 'Isi minimal salah satu: no HP atau email (dipakai untuk login).',
            'email.required_without' => 'Isi minimal salah satu: no HP atau email (dipakai untuk login).',
        ]);

        $d['email'] = filled($d['email'] ?? null) ? strtolower($d['email']) : null;
        $d['niy'] = filled($d['niy'] ?? null) ? trim($d['niy']) : null;

        return $d;
    }

    public function store(Request $request)
    {
        $d = $this->validated($request);
        $guru = User::create([
            'name' => $d['name'], 'niy' => $d['niy'], 'gender' => $d['gender'], 'phone' => $d['phone'],
            'email' => $d['email'], 'address' => $d['address'] ?? null,
            'password' => $d['password'], 'role' => 'guru', 'is_active' => $d['is_active'] ?? true,
        ]);

        return back()->with('success', "Guru {$guru->name} ditambahkan. Login di /masuk dengan " . ($guru->email ?: $guru->phone) . ' dan password yang Anda buat.');
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->role === 'guru', 404);
        $d = $this->validated($request, $user);
        $user->fill([
            'name' => $d['name'], 'niy' => $d['niy'], 'gender' => $d['gender'], 'phone' => $d['phone'],
            'email' => $d['email'], 'address' => $d['address'] ?? null, 'is_active' => $d['is_active'] ?? true,
        ]);
        if (filled($d['password'] ?? null)) {
            $user->password = $d['password'];
        }
        $user->save();

        return back()->with('success', "Data guru {$user->name} diperbarui" . (filled($d['password'] ?? null) ? ' (password diganti).' : '.'));
    }

    public function destroy(User $user)
    {
        abort_unless($user->role === 'guru', 404);
        DB::transaction(function () use ($user) {
            DB::table('feature_user')->where('user_id', $user->id)->delete();
            $user->delete();
        });

        return back()->with('success', "Guru {$user->name} dihapus.");
    }
}

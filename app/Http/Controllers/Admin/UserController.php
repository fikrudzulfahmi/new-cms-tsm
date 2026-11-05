<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $users = User::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->query('role'), fn ($q, $role) => $q->where('role', $role))
            ->orderByRaw("FIELD(role, 'admin', 'editor', 'writer')")
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'is_active' => (bool) $u->is_active,
                'last_login_at' => optional($u->last_login_at)?->toIso8601String(),
                'created_at' => optional($u->created_at)?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => ['q' => $search, 'role' => $request->query('role')],
            'roles' => ['admin' => 'Administrator', 'editor' => 'Editor', 'writer' => 'Penulis'],
            'counts' => [
                'all' => User::count(),
                'admin' => User::where('role', 'admin')->count(),
                'editor' => User::where('role', 'editor')->count(),
                'writer' => User::where('role', 'writer')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(User::CMS_ROLES)],
            'is_active' => ['boolean'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        return back()->with('success', "Akun {$data['name']} ({$data['role']}) berhasil dibuat.");
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(User::CMS_ROLES)],
            'is_active' => ['boolean'],
        ]);

        // Jangan sampai admin terakhir kehilangan akses.
        if ($user->isAdmin() && ($data['role'] !== 'admin' || ($data['is_active'] ?? true) === false)) {
            $admins = User::where('role', 'admin')->where('is_active', true)->count();
            if ($admins <= 1) {
                return back()->with('error', 'Minimal harus ada satu administrator aktif.');
            }
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'is_active' => $data['is_active'] ?? $user->is_active,
        ]);

        return back()->with('success', 'Data pengguna diperbarui.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', "Kata sandi {$user->name} berhasil direset.");
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Minimal harus ada satu administrator.');
        }

        $user->delete();

        return back()->with('success', 'Pengguna dihapus.');
    }
}

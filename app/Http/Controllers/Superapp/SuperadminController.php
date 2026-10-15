<?php

namespace App\Http\Controllers\Superapp;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SuperadminController extends Controller
{
    private function features(): array { return config('superapp.features'); }

    public function dashboard()
    {
        $counts = User::whereIn('role', ['guru', 'siswa'])->selectRaw('role, count(*) c')->groupBy('role')->pluck('c', 'role');
        $plots = DB::table('feature_user')->selectRaw('feature, count(distinct user_id) c')->groupBy('feature')->pluck('c', 'feature');
        return Inertia::render('Superadmin/Dashboard', [
            'features' => $this->features(),
            'stats' => ['guru' => $counts['guru'] ?? 0, 'siswa' => $counts['siswa'] ?? 0, 'plots' => $plots],
        ]);
    }

    /** Halaman menu yang belum dibangun. */
    public function comingSoon(string $page)
    {
        $pages = [
            'siswa' => ['Data Siswa', 'Data siswa ditarik dari aplikasi Data Induk Siswa yang sudah ada (sinkronisasi), termasuk kelas & angkatan.', ['Sinkronisasi dari aplikasi Data Induk Siswa', 'Daftar siswa per kelas / angkatan', 'Buat akun login siswa otomatis']],
            'guru' => ['Data Guru', 'Pengelolaan data guru & staf beserta akun loginnya.', ['Daftar guru (NIP, mapel, kontak)', 'Buat akun login guru', 'Import dari Excel']],
        ];
        abort_unless(isset($pages[$page]), 404);
        [$title, $desc, $plan] = $pages[$page];
        return Inertia::render('Superapp/ComingSoon', ['title' => $title, 'desc' => $desc, 'plan' => [['label' => 'Rencana fitur', 'items' => $plan]], 'layout' => 'superadmin']);
    }

    public function feature(string $feature)
    {
        $f = $this->features()[$feature] ?? abort(404);
        $plan = collect($f['roles'])->map(fn ($r) => ['label' => $r['label'], 'items' => $r['plan']])->values();
        return Inertia::render('Superapp/ComingSoon', ['title' => $f['name'], 'desc' => $f['full'] . ' — ' . $f['desc'], 'plan' => $plan, 'layout' => 'superadmin', 'feature' => $feature]);
    }

    // ---------- Pengguna & Akses (plotting user ke fitur) ----------
    public function users(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $role = $request->query('role');
        $users = User::whereIn('role', User::APP_ROLES)
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")))
            ->when(in_array($role, User::APP_ROLES, true), fn ($w) => $w->where('role', $role))
            ->orderByRaw("FIELD(role,'superadmin','guru','siswa')")->orderBy('name')
            ->paginate(25)->withQueryString();
        $plots = DB::table('feature_user')->whereIn('user_id', $users->pluck('id'))->get(['user_id', 'feature', 'role'])->groupBy('user_id');
        $users->getCollection()->transform(fn ($u) => [
            'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->role, 'is_active' => $u->is_active,
            'last_login_at' => $u->last_login_at?->toDateTimeString(),
            'plots' => ($plots[$u->id] ?? collect())->map(fn ($p) => $p->feature . ':' . $p->role)->values(),
        ]);
        return Inertia::render('Superadmin/Users', ['users' => $users, 'features' => $this->features(), 'filters' => ['q' => $q, 'role' => $role]]);
    }

    private function rules(?User $user = null): array
    {
        return [
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in(User::APP_ROLES)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'max:100'],
            'is_active' => 'boolean',
            'plots' => 'array',
            'plots.*' => 'string|max:61',
        ];
    }

    private function syncPlots(User $user, array $plots): void
    {
        $valid = [];
        foreach ($this->features() as $key => $f) foreach (array_keys($f['roles']) as $r) $valid["$key:$r"] = [$key, $r];
        DB::table('feature_user')->where('user_id', $user->id)->delete();
        if ($user->role === 'superadmin') return; // superadmin otomatis akses semua
        // siswa hanya boleh peran siswa/pembaca; guru tidak boleh peran siswa
        $ok = fn ($r) => $user->role === 'siswa' ? in_array($r, ['siswa', 'pembaca'], true) : $r !== 'siswa';
        $rows = collect($plots)->unique()->filter(fn ($p) => isset($valid[$p]) && $ok($valid[$p][1]))
            ->map(fn ($p) => ['user_id' => $user->id, 'feature' => $valid[$p][0], 'role' => $valid[$p][1], 'created_at' => now(), 'updated_at' => now()])->values()->all();
        if ($rows) DB::table('feature_user')->insert($rows);
    }

    public function usersStore(Request $request)
    {
        $d = $request->validate($this->rules());
        $user = User::create(['name' => $d['name'], 'email' => $d['email'], 'role' => $d['role'], 'password' => $d['password'], 'is_active' => $d['is_active'] ?? true]);
        $this->syncPlots($user, $d['plots'] ?? []);
        return back()->with('success', "Akun {$user->name} dibuat.");
    }

    public function usersUpdate(Request $request, User $user)
    {
        abort_if($user->isCmsUser(), 404);
        $d = $request->validate($this->rules($user));
        if ($user->id === $request->user()->id && ($d['role'] !== 'superadmin' || !($d['is_active'] ?? true))) {
            return back()->with('error', 'Anda tidak bisa menurunkan peran atau menonaktifkan akun sendiri.');
        }
        $user->fill(['name' => $d['name'], 'email' => $d['email'], 'role' => $d['role'], 'is_active' => $d['is_active'] ?? true]);
        if (!empty($d['password'])) $user->password = $d['password'];
        $user->save();
        $this->syncPlots($user, $d['plots'] ?? []);
        return back()->with('success', "Akun {$user->name} diperbarui.");
    }

    public function usersDestroy(Request $request, User $user)
    {
        abort_if($user->isCmsUser(), 404);
        if ($user->id === $request->user()->id) return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        $user->delete();
        return back()->with('success', 'Akun dihapus.');
    }
}

<?php

namespace App\Http\Controllers\Superapp;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

// /app/{fitur}/admin/anggota — admin fitur menentukan siapa saja yang bisa mengakses fiturnya.
// Peran "admin" tidak bisa diberikan dari sini (hanya superadmin).
class FeatureMemberController extends Controller
{
    private function feature(string $key): array
    {
        return config("superapp.features.$key") ?? abort(404);
    }

    private function authorizeAdmin(Request $request, string $feature): void
    {
        $u = $request->user();
        if (!($u->role === 'superadmin' || $u->hasFeature($feature, 'admin'))) {
            abort(redirect($u->homeUrl())->with('error', 'Anda bukan admin fitur ' . ($this->feature($feature)['name']) . '.'));
        }
    }

    /** Peran anggota (selain admin) + jenis akun yang boleh memegangnya. */
    private function memberRoles(array $f): array
    {
        return collect($f['roles'])->except('admin')->map(fn ($r, $key) => [
            'key' => $key, 'label' => $r['label'],
            'accounts' => match ($key) { 'siswa' => ['siswa'], 'pembaca' => ['guru', 'siswa'], default => ['guru'] },
        ])->values()->all();
    }

    public function index(Request $request, string $feature)
    {
        $this->authorizeAdmin($request, $feature);
        $f = $this->feature($feature);
        $roles = $this->memberRoles($f);
        $q = trim((string) $request->query('q', ''));
        $role = $request->query('role');
        $kelas = $request->query('kelas');

        $members = DB::table('feature_user')->join('users', 'users.id', '=', 'feature_user.user_id')
            ->where('feature_user.feature', $feature)->where('feature_user.role', '!=', 'admin')
            ->when($role, fn ($w) => $w->where('feature_user.role', $role))
            ->when($kelas, fn ($w) => $w->where('users.kelas', $kelas))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('users.name', 'like', "%$q%")->orWhere('users.nis', 'like', "$q%")->orWhere('users.niy', 'like', "$q%")))
            ->orderBy('feature_user.role')->orderBy('users.kelas')->orderBy('users.name')
            ->select(['feature_user.role as feature_role', 'users.id', 'users.name', 'users.role', 'users.nis', 'users.niy', 'users.kelas', 'users.is_active'])
            ->paginate(50)->withQueryString();

        $counts = DB::table('feature_user')->where('feature', $feature)->where('role', '!=', 'admin')
            ->selectRaw('role, count(*) c')->groupBy('role')->pluck('c', 'role');

        // kandidat: guru aktif (+ penanda sudah anggota per peran), daftar kelas siswa
        $memberOf = DB::table('feature_user')->where('feature', $feature)->where('role', '!=', 'admin')
            ->get(['user_id', 'role'])->groupBy('user_id')->map(fn ($r) => $r->pluck('role')->values());
        $teachers = User::where('role', 'guru')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'niy'])
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'niy' => $t->niy, 'roles' => $memberOf[$t->id] ?? []]);
        $classes = User::where('role', 'siswa')->where('is_active', true)->whereNotNull('kelas')
            ->selectRaw('kelas, count(*) c')->groupBy('kelas')->orderBy('kelas')->pluck('c', 'kelas');

        return Inertia::render('Superapp/FeatureMembers', [
            'feature' => ['key' => $feature, 'name' => $f['name'], 'full' => $f['full'], 'adminLabel' => $f['roles']['admin']['label']],
            'roles' => $roles,
            'counts' => $counts,
            'members' => $members,
            'teachers' => $teachers,
            'classes' => $classes,
            'filters' => ['q' => $q, 'role' => $role, 'kelas' => $kelas],
            'layout' => $request->user()->role === 'superadmin' ? 'superadmin' : 'portal',
        ]);
    }

    public function store(Request $request, string $feature)
    {
        $this->authorizeAdmin($request, $feature);
        $f = $this->feature($feature);
        $roles = collect($this->memberRoles($f))->keyBy('key');
        $d = $request->validate([
            'role' => ['required', Rule::in($roles->keys()->all())],
            'user_ids' => 'array',
            'user_ids.*' => 'integer',
            'kelas' => 'array',
            'kelas.*' => 'string|max:50',
            'nis' => 'nullable|string|max:2000',
        ], ['role.in' => 'Peran tidak valid. Admin fitur hanya bisa ditunjuk oleh superadmin.']);
        $accounts = $roles[$d['role']]['accounts'];

        $ids = collect();
        if (!empty($d['user_ids'])) {
            $ids = $ids->merge(User::whereIn('id', $d['user_ids'])->whereIn('role', $accounts)->pluck('id'));
        }
        if (in_array('siswa', $accounts, true) && !empty($d['kelas'])) {
            $ids = $ids->merge(User::where('role', 'siswa')->where('is_active', true)->whereIn('kelas', $d['kelas'])->pluck('id'));
        }
        $notFound = [];
        if (in_array('siswa', $accounts, true) && filled($d['nis'] ?? null)) {
            // NIS dipisah spasi/koma/baris baru; NIS lengkap dipotong ke 5 angka pertama
            $list = collect(preg_split('/[\s,;]+/', $d['nis'], -1, PREG_SPLIT_NO_EMPTY))
                ->map(fn ($n) => substr(preg_replace('/\D/', '', explode('/', $n)[0]), 0, 5))->filter()->unique();
            $found = User::where('role', 'siswa')->whereIn('nis', $list)->pluck('id', 'nis');
            $notFound = $list->reject(fn ($n) => isset($found[$n]))->values()->all();
            $ids = $ids->merge($found->values());
        }
        $ids = $ids->unique()->values();
        if ($ids->isEmpty()) {
            $label = $roles[$d['role']]['label'];
            return back()->withErrors(['user_ids' => $notFound ? 'NIS tidak ditemukan: ' . implode(', ', $notFound)
                : "Pilih minimal satu anggota. Peran $label hanya untuk akun " . implode('/', $accounts) . '.']);
        }

        $existing = DB::table('feature_user')->where(['feature' => $feature, 'role' => $d['role']])->whereIn('user_id', $ids)->pluck('user_id')->all();
        $new = $ids->diff($existing);
        DB::table('feature_user')->insert($new->map(fn ($id) => [
            'user_id' => $id, 'feature' => $feature, 'role' => $d['role'], 'created_at' => now(), 'updated_at' => now(),
        ])->all());

        $msg = $new->count() . " anggota ditambahkan sebagai {$roles[$d['role']]['label']}";
        if ($existing) $msg .= ', ' . count($existing) . ' sudah terdaftar sebelumnya';
        if ($notFound) $msg .= '. NIS tidak ditemukan: ' . implode(', ', $notFound);

        return back()->with('success', $msg . '.');
    }

    public function destroy(Request $request, string $feature)
    {
        $this->authorizeAdmin($request, $feature);
        $f = $this->feature($feature);
        $d = $request->validate([
            'role' => ['required', Rule::in(array_keys(collect($f['roles'])->except('admin')->all()))],
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer',
        ]);
        $n = DB::table('feature_user')->where(['feature' => $feature, 'role' => $d['role']])->whereIn('user_id', $d['user_ids'])->delete();

        return back()->with('success', "$n anggota dikeluarkan dari {$f['name']}.");
    }
}

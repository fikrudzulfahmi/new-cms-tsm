<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\ClassMember;
use App\Models\SchoolClass;
use App\Models\SyncRun;
use App\Models\User;
use App\Services\IndukApi;
use App\Services\SyncInduk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request, IndukApi $api)
    {
        $q = trim((string) $request->query('q', ''));
        $active = AcademicYear::active();

        return Inertia::render('Lms/Superadmin/Index', [
            'years' => AcademicYear::orderByDesc('name')->get(['id', 'name', 'is_active']),
            'activeYear' => $active?->name,
            'indukReady' => $api->configured(),
            'counts' => [
                'siswa' => User::where('role', 'siswa')->where('is_active', true)->count(),
                'guru' => User::where('role', 'guru')->where('is_active', true)->count(),
                'rombel' => SchoolClass::where('is_active', true)->count(),
            ],
            'classes' => SchoolClass::where('is_active', true)->orderBy('name')->get(['id', 'name'])->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name,
                'members' => $active ? ClassMember::where('class_id', $c->id)->where('academic_year_id', $active->id)->whereNull('left_at')->count() : 0,
            ]),
            'runs' => SyncRun::latest('id')->limit(5)->get(['id', 'trigger', 'status', 'message', 'classes', 'created', 'updated', 'deactivated', 'conflicts', 'started_at']),
            'accounts' => User::whereIn('role', ['guru', 'siswa'])
                ->when($q !== '', fn ($b) => $b->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('username', 'like', "%{$q}%")))
                ->orderBy('role')->orderBy('name')->limit(30)
                ->get(['id', 'name', 'username', 'role', 'is_active', 'must_change_password']),
            'q' => $q,
        ]);
    }

    public function storeYear(Request $request)
    {
        $d = $request->validate(['name' => ['required', 'regex:/^\d{4}\/\d{4}$/', 'unique:core_academic_years,name']], [
            'name.regex' => 'Format tahun ajaran: 2026/2027.', 'name.unique' => 'Tahun ajaran itu sudah ada.',
        ]);
        [$a, $b] = array_map('intval', explode('/', $d['name']));
        if ($b !== $a + 1) {
            return back()->withErrors(['name' => 'Tahun kedua harus tahun pertama + 1.']);
        }
        $year = AcademicYear::create(['name' => $d['name']]);
        ActivityLog::record('create', 'lms', 'Tahun ajaran '.$year->name.' dibuat', $year);

        return back()->with('success', 'Tahun ajaran ditambahkan.');
    }

    public function activateYear(AcademicYear $year)
    {
        $year->activate();
        ActivityLog::record('update', 'lms', 'Tahun ajaran aktif: '.$year->name, $year);

        return back()->with('success', 'Tahun ajaran aktif: '.$year->name.'. Jalankan sinkronisasi agar keanggotaan kelas mengikuti.');
    }

    public function destroyYear(AcademicYear $year)
    {
        if ($year->is_active || ClassMember::where('academic_year_id', $year->id)->exists()) {
            return back()->withErrors(['name' => 'Tahun ajaran aktif atau yang sudah punya data kelas tidak bisa dihapus.']);
        }
        $year->delete();
        ActivityLog::record('delete', 'lms', 'Tahun ajaran '.$year->name.' dihapus');

        return back()->with('success', 'Tahun ajaran dihapus.');
    }

    public function sync(SyncInduk $sync)
    {
        $run = $sync->run('manual');
        ActivityLog::record('update', 'lms', 'Sinkron Data Induk: '.$run->status, null, [
            'classes' => $run->classes, 'created' => $run->created, 'updated' => $run->updated,
            'deactivated' => $run->deactivated, 'conflicts' => $run->conflicts,
        ]);

        return $run->status === 'failed'
            ? back()->withErrors(['sync' => $run->message])
            : back()->with('success', sprintf('Sinkron %s: %d rombel, %d baru, %d berubah, %d nonaktif, %d bentrok.',
                $run->status === 'ok' ? 'selesai' : 'sebagian', $run->classes, $run->created, $run->updated, $run->deactivated, $run->conflicts));
    }

    public function storeTeacher(Request $request)
    {
        $d = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'regex:/^[A-Za-z0-9._-]{4,40}$/', 'unique:users,username'],
        ], ['username.regex' => 'NIK/username 4-40 karakter tanpa spasi.', 'username.unique' => 'Username itu sudah dipakai.']);

        $u = new User;
        $u->forceFill([
            'name' => trim($d['name']), 'username' => $d['username'], 'role' => 'guru',
            'password' => Hash::make($d['username']), 'is_active' => true, 'must_change_password' => true,
        ])->save();
        ActivityLog::record('create', 'lms', 'Akun guru dibuat: '.$u->name, $u);

        return back()->with('success', 'Akun guru dibuat. Password awal = username; wajib diganti saat login pertama.');
    }

    public function resetPassword(User $user)
    {
        abort_unless(in_array($user->role, ['guru', 'siswa'], true) && $user->username, 404);
        $user->forceFill([
            'password' => Hash::make($user->username), 'must_change_password' => true,
            'password_changed_at' => null, 'remember_token' => null,
        ])->save();
        DB::table('sessions')->where('user_id', $user->id)->delete();
        ActivityLog::record('update', 'lms', 'Password direset: '.$user->name, $user);

        return back()->with('success', 'Password '.$user->name.' direset ke username. Wajib diganti saat login.');
    }

    public function toggleActive(User $user)
    {
        abort_unless(in_array($user->role, ['guru', 'siswa'], true), 404);
        $user->forceFill(['is_active' => ! $user->is_active])->save();
        if (! $user->is_active) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
        ActivityLog::record('update', 'lms', ($user->is_active ? 'Akun diaktifkan: ' : 'Akun dinonaktifkan: ').$user->name, $user);

        return back()->with('success', 'Status akun diperbarui.');
    }
}

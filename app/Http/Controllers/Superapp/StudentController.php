<?php

namespace App\Http\Controllers\Superapp;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\IndukApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

// Superadmin > Data Siswa: tarik siswa per kelas dari API Data Induk, pratinjau, lalu simpan jadi akun siswa.
class StudentController extends Controller
{
    public function index(Request $request, IndukApi $api)
    {
        $q = trim((string) $request->query('q', ''));
        $kelas = $request->query('kelas');
        $students = User::where('role', 'siswa')
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%$q%")->orWhere('nis', 'like', "$q%")->orWhere('nisn', 'like', "$q%")))
            ->when($kelas, fn ($w) => $w->where('kelas', $kelas))
            ->orderBy('kelas')->orderBy('name')
            ->paginate(30, ['id', 'name', 'nis', 'nisn', 'gender', 'birth_date', 'kelas', 'is_active', 'last_login_at', 'synced_at'])
            ->withQueryString();

        return Inertia::render('Superadmin/Students', [
            'students' => $students,
            'kelasLokal' => User::where('role', 'siswa')->whereNotNull('kelas')->select('kelas', DB::raw('count(*) as total'))->groupBy('kelas')->orderBy('kelas')->get(),
            'filters' => ['q' => $q, 'kelas' => $kelas],
            'apiReady' => $api->configured(),
            'apiUrl' => config('services.induk.url'),
        ]);
    }

    /** JSON: daftar kelas dari API. */
    public function kelas(IndukApi $api)
    {
        try { return response()->json(['data' => $api->kelas()]); }
        catch (\RuntimeException $e) { return response()->json(['message' => $e->getMessage()], 502); }
    }

    /** JSON: pratinjau siswa satu kelas + status dibanding data lokal. */
    public function preview(Request $request, IndukApi $api)
    {
        $d = $request->validate(['kelas_id' => 'required|string|max:50']);
        try { $rows = $api->siswa($d['kelas_id']); }
        catch (\RuntimeException $e) { return response()->json(['message' => $e->getMessage()], 502); }

        $kelasNama = collect($api->kelas())->firstWhere('id', $d['kelas_id'])['nama'] ?? null;
        $local = User::whereIn('nis', collect($rows)->pluck('nis')->filter())->get()->keyBy('nis');

        $out = collect($rows)->map(function ($s) use ($local, $kelasNama) {
            $u = $local[$s['nis']] ?? null;
            $problem = match (true) {
                $s['nis'] === '' => 'NIS kosong',
                $s['name'] === '' => 'Nama kosong',
                !$s['birth_date'] => 'Tanggal lahir kosong/tidak valid (siswa tidak bisa login)',
                $u && $u->role !== 'siswa' => 'NIS sudah dipakai akun non-siswa',
                default => null,
            };
            $status = $problem ? 'masalah' : (!$u ? 'baru' : (
                ($u->name !== $s['name'] || $u->kelas !== $kelasNama || $u->birth_date?->format('Y-m-d') !== $s['birth_date'] || $u->nisn !== $s['nisn'] || $u->gender !== $s['gender']) ? 'berubah' : 'sama'));
            return $s + ['kelas' => $kelasNama, 'state' => $status, 'problem' => $problem,
                'kelas_lama' => $u && $u->kelas !== $kelasNama ? $u->kelas : null];
        });
        // NIS ganda di dalam respons
        $dup = $out->pluck('nis')->filter()->duplicates()->all();
        $out = $out->map(fn ($s) => in_array($s['nis'], $dup, true) ? array_merge($s, ['state' => 'masalah', 'problem' => 'NIS ganda di data induk']) : $s);

        return response()->json(['kelas' => $kelasNama, 'data' => $out->values()]);
    }

    /** Simpan hasil tarik (data diambil ulang dari API, bukan dari browser). */
    public function import(Request $request, IndukApi $api)
    {
        $d = $request->validate(['kelas_id' => 'required|string|max:50', 'nis' => 'required|array', 'nis.*' => 'string|max:20']);
        try {
            $rows = collect($api->siswa($d['kelas_id']))->keyBy('nis');
            $kelasNama = collect($api->kelas())->firstWhere('id', $d['kelas_id'])['nama'] ?? null;
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        $dup = $rows->keys()->duplicates();
        $created = $updated = $skipped = 0;
        DB::transaction(function () use ($d, $rows, $kelasNama, $dup, &$created, &$updated, &$skipped) {
            foreach (array_unique($d['nis']) as $nis) {
                $s = $rows[$nis] ?? null;
                if (!$s || $s['name'] === '' || !$s['birth_date'] || $dup->contains($nis)) { $skipped++; continue; }
                $u = User::where('nis', $nis)->first();
                if ($u && $u->role !== 'siswa') { $skipped++; continue; }
                $attrs = ['name' => $s['name'], 'nisn' => $s['nisn'], 'gender' => $s['gender'], 'birth_date' => $s['birth_date'],
                    'kelas' => $kelasNama, 'induk_id' => $s['induk_id'] ?: null, 'synced_at' => now(),
                    'is_active' => $s['status'] === 'aktif'];
                if ($u) { $u->fill($attrs)->save(); $updated++; }
                else {
                    // password acak: siswa login pakai NIS + tanggal lahir, bukan password
                    User::create($attrs + ['nis' => $nis, 'role' => 'siswa', 'email' => null, 'password' => Str::random(40)]);
                    $created++;
                }
            }
        });
        $msg = "Kelas {$kelasNama}: {$created} siswa baru, {$updated} diperbarui" . ($skipped ? ", {$skipped} dilewati" : '') . '.';
        return redirect('/superadmin/siswa?kelas=' . urlencode((string) $kelasNama))->with('success', $msg);
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->role === 'siswa', 404);
        $d = $request->validate(['is_active' => 'required|boolean']);
        $user->update($d);
        return back()->with('success', "Akun {$user->name} " . ($d['is_active'] ? 'diaktifkan.' : 'dinonaktifkan.'));
    }
}

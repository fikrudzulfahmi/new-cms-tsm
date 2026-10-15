<?php

namespace App\Http\Controllers\Superapp;

use App\Http\Controllers\Controller;
use App\Models\IndukKelas;
use App\Models\User;
use App\Services\IndukApi;
use App\Services\SyncSiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Superadmin > Data Siswa.
 *
 * Tarik = pertama kali satu kelas diambil dari aplikasi Data Induk. Setelah kelas tercatat ditarik,
 * penarikan ulang tidak diizinkan lagi - perubahannya diikuti lewat tombol Sinkronkan pada kelas itu
 * (menambah siswa baru, memperbarui data yang berubah, menonaktifkan yang sudah tidak ada di induk).
 */
class StudentController extends Controller
{
    public function index(Request $request, IndukApi $api)
    {
        $q = trim((string) $request->query('q', ''));
        $kelas = $request->query('kelas');

        $students = User::where('role', 'siswa')
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%$q%")->orWhere('nis', 'like', "$q%")->orWhere('nis_lengkap', 'like', "%$q%")->orWhere('nisn', 'like', "$q%")))
            ->when($kelas, fn ($w) => $w->where('kelas', $kelas))
            ->orderBy('kelas')->orderBy('name')
            ->paginate(30, ['id', 'name', 'nis', 'nis_lengkap', 'nisn', 'gender', 'birth_date', 'kelas', 'is_active', 'last_login_at', 'synced_at'])
            ->withQueryString();

        return Inertia::render('Superadmin/Students', [
            'students' => $students,
            'kelasLokal' => User::where('role', 'siswa')->whereNotNull('kelas')->select('kelas', DB::raw('count(*) as total'))->groupBy('kelas')->orderBy('kelas')->get(),
            'kelasTertarik' => IndukKelas::orderBy('nama')->get(['induk_id', 'nama', 'jurusan', 'jumlah_induk', 'jumlah_lokal', 'ditarik_at', 'disinkron_at', 'catatan']),
            'filters' => ['q' => $q, 'kelas' => $kelas],
            'apiReady' => $api->configured(),
            'apiUrl' => config('services.induk.url'),
        ]);
    }

    /** JSON: daftar kelas dari Data Induk + status penarikan di Superapp. */
    public function kelas(IndukApi $api)
    {
        try {
            $list = $api->kelas();
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        $tertarik = IndukKelas::get()->keyBy('induk_id');
        $lokal = User::where('role', 'siswa')->whereNotNull('kelas_induk_id')
            ->select('kelas_induk_id', DB::raw('count(*) as total'))->groupBy('kelas_induk_id')->pluck('total', 'kelas_induk_id');

        foreach ($list as &$k) {
            $row = $tertarik[$k['id']] ?? null;
            $k['sudah_ditarik'] = $row !== null;
            $k['jumlah_lokal'] = (int) ($lokal[$k['id']] ?? 0);
            $k['disinkron_at'] = $row?->disinkron_at?->translatedFormat('d M Y H:i');
        }

        return response()->json(['data' => $list]);
    }

    /** JSON: pratinjau satu kelas + perbandingan dengan data lokal. */
    public function preview(Request $request, IndukApi $api)
    {
        $d = $request->validate(['kelas_id' => 'required|string|max:50']);

        try {
            $rows = $api->siswa($d['kelas_id']);
            $info = collect($api->kelas())->firstWhere('id', $d['kelas_id']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        $kelasNama = $info['nama'] ?? null;
        $tertarik = IndukKelas::where('induk_id', $d['kelas_id'])->first();

        $kunci = collect($rows)->pluck('nis')->filter();
        $local = User::whereIn('nis', $kunci)->get()->keyBy('nis');
        $ganda = $kunci->duplicates()->all();

        $out = collect($rows)->map(function ($s) use ($local, $kelasNama, $ganda) {
            $u = $local[$s['nis']] ?? null;
            $problem = match (true) {
                $s['nis'] === '' => 'NIS kosong di Data Induk',
                $s['name'] === '' => 'Nama kosong',
                !$s['birth_date'] => 'Tanggal lahir kosong/tidak valid (siswa tidak bisa login)',
                in_array($s['nis'], $ganda, true) => 'NIS (5 angka pertama) ganda di Data Induk',
                $u && $u->role !== 'siswa' => 'NIS ini dipakai akun non-siswa',
                default => null,
            };
            $state = $problem ? 'masalah' : (!$u ? 'baru' : (
                ($u->name !== $s['name'] || $u->kelas !== $kelasNama || $u->nis_lengkap !== $s['nis_lengkap']
                    || $u->birth_date?->format('Y-m-d') !== $s['birth_date'] || $u->nisn !== $s['nisn']
                    || $u->gender !== $s['gender']) ? 'berubah' : 'sama'));

            return $s + ['kelas' => $kelasNama, 'state' => $state, 'problem' => $problem,
                'kelas_lama' => $u && $u->kelas !== $kelasNama ? $u->kelas : null,
                'akun_aktif' => $u?->is_active];
        });

        // siswa lokal kelas ini yang sudah tidak ada lagi di Data Induk
        $hilang = $tertarik ? User::where('role', 'siswa')->where('kelas_induk_id', $d['kelas_id'])
            ->whereNotIn('nis', $kunci->all() ?: [''])->get(['id', 'name', 'nis', 'nis_lengkap', 'is_active'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'nis' => $u->nis, 'nis_lengkap' => $u->nis_lengkap, 'is_active' => (bool) $u->is_active])
            : collect();

        return response()->json([
            'kelas' => $kelasNama,
            'kelas_id' => $d['kelas_id'],
            'sudah_ditarik' => $tertarik !== null,
            'disinkron_at' => $tertarik?->disinkron_at?->translatedFormat('d M Y H:i'),
            'data' => $out->values(),
            'hilang' => $hilang->values(),
        ]);
    }

    /** Tarik pertama kali satu kelas: hanya siswa yang dicentang yang disimpan. */
    public function import(Request $request, IndukApi $api, SyncSiswa $sync)
    {
        $d = $request->validate(['kelas_id' => 'required|string|max:50', 'nis' => 'required|array', 'nis.*' => 'string|max:20']);

        if ($ada = IndukKelas::where('induk_id', $d['kelas_id'])->first()) {
            return back()->with('error', "Kelas {$ada->nama} sudah pernah ditarik. Tarik ulang tidak diizinkan - pakai tombol Sinkronkan supaya datanya mengikuti Data Induk.");
        }

        $kelas = null;

        try {
            $info = collect($api->kelas())->firstWhere('id', $d['kelas_id']);
            if (!$info) {
                return back()->with('error', 'Kelas tidak ditemukan di Data Induk.');
            }

            $kelas = IndukKelas::create([
                'induk_id' => $d['kelas_id'],
                'nama' => $info['nama'],
                'tingkat' => $info['tingkat'],
                'jurusan' => $info['jurusan'],
                'jumlah_induk' => (int) ($info['jumlah_siswa'] ?? 0),
                'ditarik_at' => now(),
            ]);

            $c = $sync->impor($kelas, array_values(array_unique($d['nis'])));
        } catch (\RuntimeException $e) {
            $kelas?->delete();

            return back()->with('error', $e->getMessage());
        }

        return redirect('/superadmin/siswa?kelas=' . urlencode((string) $kelas->nama))->with('success', SyncSiswa::pesan($kelas->nama, $c));
    }

    /** Sinkronkan satu kelas; tanpa kelas_id -> seluruh kelas yang sudah ditarik. */
    public function sync(Request $request, SyncSiswa $sync)
    {
        $d = $request->validate(['kelas_id' => 'nullable|string|max:50']);

        if (blank($d['kelas_id'] ?? null)) {
            return $this->syncAll($sync);
        }

        $kelas = IndukKelas::where('induk_id', $d['kelas_id'])->first();
        if (!$kelas) {
            return back()->with('error', 'Kelas ini belum pernah ditarik. Pakai tombol Tarik Data lebih dulu.');
        }

        try {
            $c = $sync->kelas($kelas);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', SyncSiswa::pesan($kelas->nama, $c));
    }

    public function syncAll(SyncSiswa $sync)
    {
        if (IndukKelas::count() === 0) {
            return back()->with('error', 'Belum ada kelas yang ditarik dari Data Induk.');
        }

        $hasil = $sync->semua();
        $pesan = [];
        $gagal = [];

        foreach ($hasil as $nama => $c) {
            if (isset($c['error'])) {
                $gagal[] = "{$nama}: {$c['error']}";

                continue;
            }
            $pesan[] = SyncSiswa::pesan($nama, $c);
        }

        if ($gagal !== []) {
            return back()->with('error', implode(' | ', $gagal));
        }

        return back()->with('success', implode(' ', $pesan));
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->role === 'siswa', 404);
        $d = $request->validate(['is_active' => 'required|boolean']);
        $user->update($d);

        return back()->with('success', "Akun {$user->name} " . ($d['is_active'] ? 'diaktifkan.' : 'dinonaktifkan.'));
    }
}

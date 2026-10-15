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
        $plots = DB::table('feature_user')->where('role', '!=', 'admin')->selectRaw('feature, count(distinct user_id) c')->groupBy('feature')->pluck('c', 'feature');
        $admins = DB::table('feature_user')->where('role', 'admin')->selectRaw('feature, count(*) c')->groupBy('feature')->pluck('c', 'feature');
        return Inertia::render('Superadmin/Dashboard', [
            'features' => $this->features(),
            'stats' => ['guru' => $counts['guru'] ?? 0, 'siswa' => $counts['siswa'] ?? 0, 'plots' => $plots, 'admins' => $admins],
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
        $this->features()[$feature] ?? abort(404);
        return redirect("/app/$feature/admin");
    }
}

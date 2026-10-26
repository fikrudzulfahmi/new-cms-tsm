<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

// Halaman awal sementara untuk guru, siswa, dan superadmin (modul menyusul bertahap).
class HomeByRoleController extends Controller
{
    public function __invoke(Request $request)
    {
        return Inertia::render('Lms/Placeholder', ['role' => $request->user()->role]);
    }
}

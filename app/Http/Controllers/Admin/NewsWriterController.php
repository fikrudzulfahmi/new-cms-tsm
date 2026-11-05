<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class NewsWriterController extends Controller
{
    public function create()
    {
        abort_if(Gate::allows('adminOnly'), 403);

        return Inertia::render('Admin/NewsWriter/Create', [
            'roles' => ['writer' => 'Penulis'],
        ]);
    }

    public function store(Request $request)
    {
        abort_if(Gate::allows('adminOnly'), 403);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true,
        ]);

        return Inertia::location(route('admin.writers.index'))
            ->with('flash', ['success' => "Akun writer {$user->name} berhasil dibuat."]);
    }
}
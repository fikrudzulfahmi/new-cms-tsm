<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

// php artisan superapp:superadmin  -> buat/ubah akun superadmin (password diketik tersembunyi)
class MakeSuperadmin extends Command
{
    protected $signature = 'superapp:superadmin {email?} {--name=Superadmin TSM}';
    protected $description = 'Buat atau reset akun Superadmin TSM Superapp';

    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('Email superadmin');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $this->error('Email tidak valid.'); return 1; }
        $existing = User::where('email', $email)->first();
        if ($existing?->isCmsUser()) { $this->error('Email ini dipakai akun Admin CMS. Gunakan email lain.'); return 1; }
        $pass = $this->secret('Password (min 8 karakter)');
        if (strlen((string) $pass) < 8 || $pass !== $this->secret('Ulangi password')) { $this->error('Password kurang dari 8 karakter / tidak sama.'); return 1; }
        $user = User::updateOrCreate(['email' => $email], ['name' => $existing->name ?? $this->option('name'), 'password' => $pass, 'role' => 'superadmin', 'is_active' => true]);
        $this->info("Superadmin siap: {$user->email} — login di /masuk");
        return 0;
    }
}

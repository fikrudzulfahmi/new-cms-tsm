<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeSuperadmin extends Command
{
    protected $signature = 'lms:superadmin {username : Username login superadmin} {--name=Superadmin}';

    protected $description = 'Buat atau reset akun superadmin (password diketik tersembunyi)';

    public function handle(): int
    {
        $username = mb_strtolower(trim($this->argument('username')));
        if (! preg_match('/^[a-z0-9._-]{4,40}$/', $username)) {
            $this->error('Username 4-40 karakter: huruf kecil, angka, titik, strip, garis bawah.');

            return self::FAILURE;
        }
        $pw = $this->secret('Password (min 10 karakter)');
        if (strlen((string) $pw) < 10 || $pw !== $this->secret('Ulangi password')) {
            $this->error('Password kurang dari 10 karakter atau tidak sama.');

            return self::FAILURE;
        }

        $user = User::firstOrNew(['username' => $username]);
        if ($user->exists && $user->role !== 'superadmin') {
            $this->error('Username itu dipakai akun non-superadmin.');

            return self::FAILURE;
        }
        $user->forceFill([
            'name' => $user->name ?: $this->option('name'),
            'role' => 'superadmin',
            'password' => Hash::make($pw),
            'is_active' => true,
            'must_change_password' => false,
        ])->save();
        $this->info("Superadmin '{$username}' siap.");

        return self::SUCCESS;
    }
}

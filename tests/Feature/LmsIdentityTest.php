<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LmsIdentityTest extends TestCase
{
    use RefreshDatabase;

    private function akun(string $role, string $username, bool $wajibGanti = true): User
    {
        return User::create([
            'name' => 'Akun '.$role,
            'username' => $username,
            'password' => Hash::make($username),
            'role' => $role,
            'is_active' => true,
            'must_change_password' => $wajibGanti,
        ]);
    }

    public function test_siswa_login_dengan_username_lalu_dipaksa_ganti_password(): void
    {
        $this->akun('siswa', '12345');

        $this->post('/login', ['login' => '12345', 'password' => '12345'])->assertRedirect('/belajar');
        $this->get('/belajar')->assertRedirect('/ganti-password');
        $this->get('/ganti-password')->assertOk();
    }

    public function test_ganti_password_membuka_akses_dan_menolak_password_lemah(): void
    {
        $siswa = $this->akun('siswa', '12345');
        $this->actingAs($siswa);

        $this->put('/ganti-password', ['current_password' => '12345', 'password' => 'pendek', 'password_confirmation' => 'pendek'])
            ->assertSessionHasErrors('password');
        $this->put('/ganti-password', ['current_password' => 'salah', 'password' => 'RahasiaBaru88', 'password_confirmation' => 'RahasiaBaru88'])
            ->assertSessionHasErrors('current_password');
        $this->put('/ganti-password', ['current_password' => '12345', 'password' => 'RahasiaBaru88', 'password_confirmation' => 'RahasiaBaru88'])
            ->assertRedirect('/belajar');

        $siswa->refresh();
        $this->assertFalse($siswa->must_change_password);
        $this->assertTrue(Hash::check('RahasiaBaru88', $siswa->password));
        $this->get('/belajar')->assertOk();
    }

    public function test_password_baru_tidak_boleh_sama_dengan_username(): void
    {
        $guru = $this->akun('guru', '3573012345678901');
        $this->actingAs($guru)
            ->put('/ganti-password', ['current_password' => '3573012345678901', 'password' => '3573012345678901', 'password_confirmation' => '3573012345678901'])
            ->assertSessionHasErrors('password');
    }

    public function test_redirect_per_role_dan_batas_akses(): void
    {
        $guru = $this->akun('guru', 'guru1', false);
        $this->post('/login', ['login' => 'guru1', 'password' => 'guru1'])->assertRedirect('/ruang-guru');
        $this->get('/ruang-guru')->assertOk();
        $this->get('/belajar')->assertForbidden();
        $this->get('/admin')->assertForbidden();
        $this->get('/superadmin')->assertForbidden();
    }

    public function test_admin_cms_tetap_login_dengan_email(): void
    {
        User::create(['name' => 'Admin', 'email' => 'admin@contoh.test', 'password' => Hash::make('rahasia123'), 'role' => 'admin', 'is_active' => true]);

        $this->post('/login', ['login' => 'admin@contoh.test', 'password' => 'rahasia123'])->assertRedirect('/admin');
    }

    public function test_username_tidak_masuk_activity_log_saat_gagal(): void
    {
        $this->akun('siswa', '54321');
        $this->post('/login', ['login' => '54321', 'password' => 'salah-banget']);

        $this->assertDatabaseMissing('activity_logs', ['user_name' => '54321']);
        $this->assertSame(0, ActivityLog::where('properties', 'like', '%54321%')->count());
    }

    public function test_akun_nonaktif_tidak_bisa_masuk(): void
    {
        $s = $this->akun('siswa', '11111');
        $s->update(['is_active' => false]);

        $this->post('/login', ['login' => '11111', 'password' => '11111'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_sesi_akun_yang_dinonaktifkan_diputus(): void
    {
        $s = $this->akun('siswa', '22222', false);
        $this->actingAs($s)->get('/belajar')->assertOk();

        $s->update(['is_active' => false]);
        $this->get('/belajar')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_url_tujuan_admin_tidak_dipakai_untuk_siswa(): void
    {
        $this->akun('siswa', '33333', false);
        $this->withSession(['url.intended' => 'http://localhost/admin'])
            ->post('/login', ['login' => '33333', 'password' => '33333'])->assertRedirect('/belajar');
        auth()->logout();
        $this->withSession(['url.intended' => 'https://evil.example/x'])
            ->post('/login', ['login' => '33333', 'password' => '33333'])->assertRedirect('/belajar');
    }
}

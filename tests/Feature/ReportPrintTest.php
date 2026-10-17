<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReportPrintTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Laporan',
            'email' => 'admin-laporan@contoh.test',
            'password' => Hash::make('rahasia-laporan'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_admin_mendapat_laporan_siap_cetak(): void
    {
        $admin = $this->admin();

        Post::create([
            'user_id' => $admin->id,
            'title' => 'Prestasi LKS Provinsi',
            'slug' => 'prestasi-lks-provinsi',
            'excerpt' => 'Siswa TSM meraih medali perak pada LKS tingkat provinsi.',
            'body' => '<p>Isi berita.</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/laporan/cetak?from='.now()->subDays(7)->toDateString().'&to='.now()->toDateString());

        $response->assertOk();
        $response->assertSee('Laporan Performa Website dan Pembaruan Konten', false);
        $response->assertSee('Ringkasan Performa Website', false);
        $response->assertSee('Berita yang Diterbitkan pada Periode Ini', false);
        $response->assertSee('Pembaruan Website yang Dilakukan', false);
        $response->assertSee('Isi Website Saat Ini', false);
        // Judul, ringkasan, dan penulis berita ikut tercetak.
        $response->assertSee('Prestasi LKS Provinsi', false);
        $response->assertSee('meraih medali perak', false);
        $response->assertSee('Admin Laporan', false);
    }

    public function test_rentang_tanggal_tidak_valid_ditolak(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/admin/laporan/cetak?from=2026-10-17&to=2026-09-01')
            ->assertRedirect();
    }

    public function test_editor_tidak_boleh_membuka_laporan(): void
    {
        $editor = User::create([
            'name' => 'Editor',
            'email' => 'editor-laporan@contoh.test',
            'password' => Hash::make('rahasia-editor'),
            'role' => 'editor',
            'is_active' => true,
        ]);

        $this->actingAs($editor)->get('/admin/laporan/cetak')->assertForbidden();
    }
}

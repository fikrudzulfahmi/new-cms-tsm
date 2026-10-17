<?php

namespace Tests\Feature;

use App\Http\Middleware\ClientIp;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email): User
    {
        return User::create([
            'name' => ucfirst($role).' Uji',
            'email' => $email,
            'password' => Hash::make('rahasia123'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function runClientIp(string $remoteAddr, ?string $cfHeader): Request
    {
        $request = Request::create('/admin', 'GET', [], [], [], ['REMOTE_ADDR' => $remoteAddr]);
        if ($cfHeader !== null) {
            $request->headers->set('CF-Connecting-IP', $cfHeader);
        }
        (new ClientIp)->handle($request, fn ($r) => response('ok'));

        return $request;
    }

    public function test_header_cf_dari_klien_langsung_diabaikan(): void
    {
        // Klien LAN yang menembak Nginx langsung tidak boleh mengubah IP-nya.
        $request = $this->runClientIp('192.168.33.50', '1.2.3.4');
        $this->assertSame('192.168.33.50', $request->server->get('REMOTE_ADDR'));

        // Header sampah dari klien langsung juga tidak mengubah apa pun.
        $request = $this->runClientIp('192.168.33.50', 'bukan-ip');
        $this->assertSame('192.168.33.50', $request->server->get('REMOTE_ADDR'));
    }

    public function test_header_cf_dari_proxy_lokal_dipakai(): void
    {
        $request = $this->runClientIp('127.0.0.1', '203.0.113.9');
        $this->assertSame('203.0.113.9', $request->server->get('REMOTE_ADDR'));
    }

    public function test_editor_tidak_bisa_membuka_edit_atau_statistik_berita_orang_lain(): void
    {
        $admin = $this->user('admin', 'admin@contoh.test');
        $editor = $this->user('editor', 'editor@contoh.test');

        $post = Post::create([
            'user_id' => $admin->id,
            'title' => 'Berita Admin',
            'slug' => 'berita-admin',
            'body' => '<p>Isi</p>',
            'status' => 'draft',
        ]);

        $this->actingAs($editor)->get("/admin/berita/{$post->id}/edit")->assertForbidden();
        $this->actingAs($editor)->get("/admin/berita/{$post->id}/analytics")->assertForbidden();

        // Pemilik dan admin tetap boleh.
        $this->actingAs($admin)->get("/admin/berita/{$post->id}/edit")->assertOk();
    }

    public function test_email_dan_bio_guru_tidak_dikirim_ke_halaman_publik(): void
    {
        Teacher::create([
            'name' => 'Guru Uji',
            'position' => 'Guru Produktif',
            'subject' => 'Mesin',
            'email' => 'guru@contoh.test',
            'bio' => 'Catatan internal yang panjang.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->get('/guru');
        $response->assertOk();

        $props = $response->viewData('page')['props'];
        $teachers = json_encode($props['teachers'] ?? $props);
        $this->assertStringNotContainsString('guru@contoh.test', $teachers);
        $this->assertStringNotContainsString('Catatan internal', $teachers);
    }

    public function test_pengaturan_rahasia_tidak_dikirim_dan_tidak_bisa_ditimpa(): void
    {
        Setting::create(['key' => 'induk_api_token', 'value' => 'token-rahasia', 'group' => 'sistem', 'type' => 'text']);
        Setting::create(['key' => 'site_name', 'value' => 'TSM', 'group' => 'profil', 'type' => 'text']);

        $admin = $this->user('admin', 'admin@contoh.test');
        $response = $this->actingAs($admin)->get('/admin/pengaturan');
        $response->assertOk();
        $this->assertArrayNotHasKey('induk_api_token', $response->viewData('page')['props']['settings']);

        $this->actingAs($admin)->post('/admin/pengaturan', [
            'site_name' => 'TSM Baru',
            'induk_api_token' => 'token-palsu',
        ])->assertRedirect();

        $this->assertSame('TSM Baru', Setting::where('key', 'site_name')->value('value'));
        $this->assertSame('token-rahasia', Setting::where('key', 'induk_api_token')->value('value'));
    }

    public function test_nilai_teks_pengaturan_yang_terlalu_panjang_ditolak(): void
    {
        Setting::create(['key' => 'site_tagline', 'value' => 'Tagline', 'group' => 'profil', 'type' => 'text']);
        $admin = $this->user('admin', 'admin@contoh.test');

        $this->actingAs($admin)->post('/admin/pengaturan', [
            'site_tagline' => str_repeat('a', 1500),
        ])->assertSessionHasErrors('site_tagline');
    }

    public function test_centang_hapus_gambar_menghapus_berkas(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('content/foto.jpg', 'x');

        $teacher = Teacher::create([
            'name' => 'Guru Foto', 'photo' => 'content/foto.jpg', 'sort_order' => 1, 'is_active' => true,
        ]);
        $admin = $this->user('admin', 'admin@contoh.test');

        $this->actingAs($admin)->post("/admin/guru/{$teacher->id}", [
            '_method' => 'PUT',
            'name' => 'Guru Foto',
            'remove_photo' => '1',
        ])->assertRedirect();

        $this->assertNull($teacher->fresh()->photo);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing('content/foto.jpg');
    }
}

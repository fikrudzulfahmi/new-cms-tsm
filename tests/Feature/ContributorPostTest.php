<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ContributorPostTest extends TestCase
{
    use RefreshDatabase;

    private function u(string $role, bool $write = false, string $name = 'x'): User
    {
        $u = new User;
        $u->forceFill(['name' => $name ?: $role, 'username' => $role.$name, 'role' => $role, 'password' => Hash::make('x'),
            'is_active' => true, 'can_write_posts' => $write, 'must_change_password' => false])->save();

        return $u;
    }

    private function payload(string $status): array
    {
        $c = Category::firstOrCreate(['slug' => 'umum'], ['name' => 'Umum', 'is_active' => true]);

        return ['title' => 'Judul uji berita guru', 'category_id' => $c->id, 'status' => $status, 'body' => '<p>isi</p>'];
    }

    public function test_penulis_diarahkan_ke_halaman_pilihan(): void
    {
        $g = $this->u('guru', true);
        $this->assertSame('/pilih', $g->homeUrl());
        $this->actingAs($g)->get('/pilih')->assertOk();
        $biasa = $this->u('guru', false, 'b');
        $this->assertSame('/ruang-guru', $biasa->homeUrl());
        $this->actingAs($biasa)->get('/pilih')->assertRedirect('/ruang-guru');
    }

    public function test_guru_tanpa_izin_tidak_bisa_masuk(): void
    {
        $this->actingAs($this->u('guru'))->get('/admin/berita')->assertForbidden();
    }

    public function test_guru_berizin_hanya_berita_dan_tidak_bisa_menerbitkan(): void
    {
        $g = $this->u('guru', true);
        $this->actingAs($g)->get('/admin/berita')->assertOk();
        $this->actingAs($g)->get('/admin/komentar')->assertForbidden();
        $this->actingAs($g)->get('/admin/pengaturan')->assertForbidden();

        $this->actingAs($g)->post('/admin/berita', $this->payload('published'))->assertRedirect();
        $p = Post::first();
        $this->assertSame('pending', $p->status);
        $this->assertNull($p->published_at);
        $this->assertSame($g->id, $p->user_id);
        $this->assertSame(0, Post::published()->count());
    }

    public function test_admin_menyetujui_lalu_tayang_dan_penulis_tak_bisa_ubah(): void
    {
        $g = $this->u('guru', true);
        $this->actingAs($g)->post('/admin/berita', $this->payload('pending'));
        $p = Post::first();

        $this->actingAs($g)->patch("/admin/berita/{$p->id}/setujui")->assertForbidden();
        $this->actingAs($this->u('admin', false, 'a'))->patch("/admin/berita/{$p->id}/setujui")->assertRedirect();
        $this->assertSame(1, Post::published()->count());

        $this->actingAs($g)->put("/admin/berita/{$p->id}", $this->payload('draft') + [])->assertForbidden();
    }

    public function test_siswa_tak_bisa_edit_milik_orang_lain_dan_toggle_superadmin(): void
    {
        $s = $this->u('siswa', true, 's1');
        $other = $this->u('guru', true, 'g2');
        $this->actingAs($other)->post('/admin/berita', $this->payload('pending'));
        $p = Post::first();
        $this->actingAs($s)->get("/admin/berita/{$p->id}/edit")->assertForbidden();

        $sa = $this->u('superadmin', false, 'sa');
        $t = $this->u('siswa', false, 's2');
        $this->actingAs($sa)->post("/superadmin/akun/{$t->id}/penulis")->assertRedirect();
        $this->assertTrue($t->fresh()->can_write_posts);
        $this->actingAs($s)->post("/superadmin/akun/{$t->id}/penulis")->assertForbidden();
    }
}

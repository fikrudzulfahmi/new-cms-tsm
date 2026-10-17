<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Competency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => 'Admin Uji',
            'email' => 'admin-uji@contoh.test',
            'password' => Hash::make('rahasia123'),
            'role' => 'admin',
            'is_active' => true,
        ], $attrs));
    }

    public function test_login_gagal_dan_berhasil_tercatat(): void
    {
        $admin = $this->admin();

        $this->post('/login', ['email' => $admin->email, 'password' => 'salah-sekali'])
            ->assertRedirect();
        $this->assertDatabaseHas('activity_logs', ['action' => 'login_failed', 'ip' => '127.0.0.1']);
        $this->assertStringNotContainsString('salah-sekali', json_encode(ActivityLog::first()->properties));

        $this->post('/login', ['email' => $admin->email, 'password' => 'rahasia123'])
            ->assertRedirect();
        $this->assertDatabaseHas('activity_logs', ['action' => 'login', 'user_id' => $admin->id]);
    }

    public function test_perubahan_data_admin_tercatat_dengan_subjek(): void
    {
        $admin = $this->admin();
        $competency = Competency::create([
            'title' => 'Kelistrikan Sepeda Motor', 'slug' => 'kelistrikan', 'icon' => 'bolt',
            'short_description' => 'Uji', 'sort_order' => 1, 'is_active' => true,
        ]);

        $this->actingAs($admin)->put("/admin/kompetensi/{$competency->id}", [
            'title' => 'Kelistrikan Motor', 'icon' => 'bolt', 'short_description' => 'Uji',
            'description' => 'Uji', 'sort_order' => 1, 'is_active' => 1,
        ])->assertRedirect();

        $log = ActivityLog::where('action', 'update')->first();
        $this->assertNotNull($log);
        $this->assertSame('competencies', $log->module);
        $this->assertSame($competency->id, $log->subject_id);
        $this->assertStringContainsString('Kelistrikan Motor', $log->description);
    }

    public function test_akses_ditolak_editor_tercatat_sebagai_peristiwa_keamanan(): void
    {
        $editor = $this->admin(['email' => 'editor@contoh.test', 'role' => 'editor']);

        $this->actingAs($editor)->get('/admin/pengaturan')->assertForbidden();

        $log = ActivityLog::where('action', 'denied')->first();
        $this->assertNotNull($log);
        $this->assertTrue($log->isSecurityEvent());
        $this->assertStringContainsString('/admin/pengaturan', $log->description);
    }

    public function test_halaman_log_hanya_untuk_admin(): void
    {
        $admin = $this->admin();
        $editor = $this->admin(['email' => 'editor2@contoh.test', 'role' => 'editor']);

        $response = $this->actingAs($admin)->get('/admin/aktivitas')->assertOk();
        $response->assertInertia(fn ($page) => $page->has('logs.data')->has('stats')->has('failedByIp')->has('modules'));
        $this->assertSame('Admin/Activity/Index', $response->viewData('page')['component']);
        $this->actingAs($editor)->get('/admin/aktivitas')->assertForbidden();
    }

    public function test_command_prune_menghapus_log_lama(): void
    {
        $old = ActivityLog::create(['action' => 'login', 'description' => 'lama']);
        $old->forceFill(['created_at' => now()->subDays(120)])->save();
        ActivityLog::create(['action' => 'login', 'description' => 'baru']);

        $this->artisan('activity:prune', ['--days' => 90])->assertExitCode(0);

        $this->assertDatabaseMissing('activity_logs', ['description' => 'lama']);
        $this->assertDatabaseHas('activity_logs', ['description' => 'baru']);
    }

    public function test_tidak_menyimpan_password_atau_token(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/pengaturan', [
            'school_name' => 'Sekolah Uji', 'mail_password' => 'rahasia-password', 'api_token' => 'token-rahasia',
        ])->assertRedirect();

        $dump = ActivityLog::all()->map(fn ($l) => json_encode($l->toArray()))->implode("\n");
        $this->assertStringNotContainsString('rahasia-password', $dump);
        $this->assertStringNotContainsString('token-rahasia', $dump);
    }
}

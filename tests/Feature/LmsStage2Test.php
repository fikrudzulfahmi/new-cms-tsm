<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LmsStage2Test extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $username): User
    {
        $u = new User;
        $u->forceFill(['name' => ucfirst($role), 'username' => $username, 'role' => $role,
            'password' => Hash::make($username), 'is_active' => true, 'must_change_password' => false])->save();

        return $u;
    }

    private function fakeInduk(array $siswa1, array $siswa2 = []): void
    {
        config(['services.induk.url' => 'http://induk.test/api', 'services.induk.token' => 'tok']);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake([
            'induk.test/api/kelas*' => Http::response(['data' => [
                ['id' => 1, 'nama' => 'X TSM 1', 'tingkat' => 10, 'jurusan' => 'TSM'],
                ['id' => 2, 'nama' => 'XI TSM 1', 'tingkat' => 11, 'jurusan' => 'TSM'],
                ['id' => 9, 'nama' => 'X TKJ 1', 'tingkat' => 10, 'jurusan' => 'TKJ'],
            ]]),
            'induk.test/api/siswa/1' => Http::response(['data' => $siswa1]),
            'induk.test/api/siswa/2' => Http::response(['data' => $siswa2]),
        ]);
    }

    private function s(int $id, string $nis, string $nama = 'Budi'): array
    {
        return ['id' => $id, 'nis' => $nis, 'nisn' => '00123', 'nama' => $nama, 'jk' => 'L', 'tgl_lahir' => '2009-01-01', 'status' => 'Aktif'];
    }

    public function test_hanya_superadmin_boleh_masuk_panel(): void
    {
        $this->actingAs($this->user('guru', 'guru0001'))->get('/superadmin')->assertForbidden();
        $this->actingAs($this->user('superadmin', 'root0001'))->get('/superadmin')->assertOk();
    }

    public function test_tahun_ajaran_hanya_satu_aktif(): void
    {
        $this->actingAs($this->user('superadmin', 'root0001'));
        $this->post('/superadmin/tahun-ajaran', ['name' => '2026/2027'])->assertSessionHasNoErrors();
        $this->post('/superadmin/tahun-ajaran', ['name' => '2027/2029'])->assertSessionHasErrors('name');
        $this->post('/superadmin/tahun-ajaran', ['name' => '2027/2028']);
        foreach (AcademicYear::all() as $y) {
            $this->post("/superadmin/tahun-ajaran/{$y->id}/aktifkan");
        }
        $this->assertSame(1, AcademicYear::where('is_active', true)->count());
        $this->assertSame('2027/2028', AcademicYear::active()->name);
    }

    public function test_sync_butuh_tahun_ajaran_aktif(): void
    {
        $this->fakeInduk([]);
        $this->actingAs($this->user('superadmin', 'root0001'))->post('/superadmin/sinkron')->assertSessionHasErrors('sync');
    }

    public function test_sync_membuat_siswa_username_5_digit_dan_pindah_rombel(): void
    {
        AcademicYear::create(['name' => '2026/2027'])->activate();
        $this->fakeInduk([$this->s(101, '2425.10.001'), $this->s(102, '2425.10.002', 'Sari')]);
        $this->actingAs($this->user('superadmin', 'root0001'))->post('/superadmin/sinkron')->assertSessionHas('success');

        $budi = User::where('external_id', '101')->first();
        $this->assertSame('24251', $budi->username);   // 5 angka pertama dari NIS
        $this->assertSame('siswa', $budi->role);
        $this->assertTrue($budi->must_change_password);
        $this->assertTrue(Hash::check('24251', $budi->password));
        $this->assertNull(User::where('name', 'like', '%TKJ%')->first());

        // 102 bentrok username 24251 (NIS 5 digit sama) -> dilewati, dilaporkan
        $this->assertNull(User::where('external_id', '102')->first());

        // Budi naik kelas di induk: kini ada di rombel 2. Password yang sudah diganti tidak disentuh.
        $budi->forceFill(['password' => Hash::make('rahasia-baru'), 'must_change_password' => false])->save();
        $this->fakeInduk([], [$this->s(101, '2425.10.001')]);
        $this->post('/superadmin/sinkron');
        $aktif = ClassMember::where('student_id', $budi->id)->whereNull('left_at')->get();
        $this->assertCount(1, $aktif);
        $this->assertSame('XI TSM 1', $aktif[0]->schoolClass->name);
        $this->assertTrue(Hash::check('rahasia-baru', $budi->fresh()->password));
        $this->assertFalse($budi->fresh()->must_change_password);
    }

    public function test_siswa_yang_hilang_dari_induk_dinonaktifkan_dan_gagal_api_tidak_menonaktifkan(): void
    {
        AcademicYear::create(['name' => '2026/2027'])->activate();
        $this->fakeInduk([$this->s(101, '11111.1')]);
        $admin = $this->actingAs($this->user('superadmin', 'root0001'));
        $this->post('/superadmin/sinkron');
        $budi = User::where('external_id', '101')->first();

        // Satu rombel gagal -> partial, tidak ada penonaktifan
        config(['services.induk.url' => 'http://induk.test/api', 'services.induk.token' => 'tok']);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['induk.test/api/kelas*' => Http::response(['data' => [['id' => 1, 'nama' => 'X TSM 1', 'tingkat' => 10, 'jurusan' => 'TSM']]]),
            'induk.test/api/siswa/1' => Http::response('x', 500)]);
        $this->post('/superadmin/sinkron');
        $this->assertTrue($budi->fresh()->is_active);

        // Respons sukses tanpa siswa tsb -> nonaktif
        $this->fakeInduk([$this->s(555, '55555.1', 'Lain')]);
        $this->post('/superadmin/sinkron');
        $this->assertFalse($budi->fresh()->is_active);
    }

    public function test_token_induk_tidak_bocor_ke_tabel_sync(): void
    {
        AcademicYear::create(['name' => '2026/2027'])->activate();
        $this->fakeInduk([]);
        $this->actingAs($this->user('superadmin', 'root0001'))->post('/superadmin/sinkron');
        $this->assertStringNotContainsString('tok', json_encode(\App\Models\SyncRun::all()->toArray()));
    }

    public function test_buat_guru_dan_reset_password(): void
    {
        $this->actingAs($this->user('superadmin', 'root0001'));
        $this->post('/superadmin/guru', ['name' => 'Pak Guru', 'username' => '3301020304050001'])->assertSessionHasNoErrors();
        $g = User::where('username', '3301020304050001')->first();
        $this->assertSame('guru', $g->role);
        $this->assertTrue($g->must_change_password);

        $g->forceFill(['password' => Hash::make('baru-ganti-1'), 'must_change_password' => false])->save();
        $this->post("/superadmin/akun/{$g->id}/reset");
        $g->refresh();
        $this->assertTrue(Hash::check('3301020304050001', $g->password));
        $this->assertTrue($g->must_change_password);

        $admin = $this->user('admin', 'adm00001');
        $this->post("/superadmin/akun/{$admin->id}/reset")->assertNotFound();   // akun CMS tidak bisa direset lewat sini
    }

    public function test_guru_tidak_bisa_reset_atau_sinkron(): void
    {
        $siswa = $this->user('siswa', '12345');
        $this->actingAs($this->user('guru', 'guru0001'))->post("/superadmin/akun/{$siswa->id}/reset")->assertForbidden();
        $this->post('/superadmin/sinkron')->assertForbidden();
    }

    public function test_respons_induk_kosong_tidak_menonaktifkan_siapa_pun(): void
    {
        AcademicYear::create(['name' => '2026/2027'])->activate();
        $this->fakeInduk([$this->s(101, '11111.1')]);
        $this->actingAs($this->user('superadmin', 'root0001'))->post('/superadmin/sinkron');
        $budi = User::where('external_id', '101')->first();

        $this->fakeInduk([], []);
        $this->post('/superadmin/sinkron');
        $this->assertTrue($budi->fresh()->is_active);
    }

    public function test_siswa_bentrok_username_tidak_dinonaktifkan(): void
    {
        AcademicYear::create(['name' => '2026/2027'])->activate();
        $this->fakeInduk([$this->s(101, '11111.1'), $this->s(102, '11111.2', 'Kembar')]);
        $this->actingAs($this->user('superadmin', 'root0001'))->post('/superadmin/sinkron');
        $a = User::where('external_id', '101')->first();
        $this->fakeInduk([$this->s(101, '11111.1'), $this->s(102, '11111.2', 'Kembar'), $this->s(103, '22222.1')]);
        $this->post('/superadmin/sinkron');
        $this->assertTrue($a->fresh()->is_active);
    }
}

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

    private function admin()
    {
        return $this->actingAs($this->user('superadmin', 'root0001'));
    }

    private function kelas(int $ext): \App\Models\SchoolClass
    {
        return \App\Models\SchoolClass::where('external_id', $ext)->firstOrFail();
    }

    public function test_tarik_kelas_hanya_menyimpan_kelas_jurusan_tsm_tanpa_siswa(): void
    {
        $this->fakeInduk([$this->s(101, '2425.10.001')]);
        $this->admin()->post('/superadmin/kelas/tarik')->assertSessionHas('success');
        $this->assertSame(2, \App\Models\SchoolClass::count());
        $this->assertSame(0, User::where('role', 'siswa')->count());
    }

    public function test_tarik_siswa_butuh_tahun_ajaran_aktif(): void
    {
        $this->fakeInduk([]);
        $this->admin()->post('/superadmin/kelas/tarik');
        $this->post('/superadmin/kelas/'.$this->kelas(1)->id.'/tarik')->assertSessionHasErrors('sync');
    }

    public function test_pratinjau_tidak_menyimpan_apa_pun(): void
    {
        AcademicYear::create(['name' => '2026/2027'])->activate();
        $this->fakeInduk([$this->s(101, '2425.10.001')]);
        $this->admin()->post('/superadmin/kelas/tarik');
        $this->post('/superadmin/kelas/'.$this->kelas(1)->id.'/pratinjau')->assertSessionHas('preview');
        $this->assertSame(0, User::where('role', 'siswa')->count());
        $this->assertSame(0, ClassMember::count());
    }

    public function test_tarik_satu_kelas_tidak_menyentuh_kelas_lain_dan_username_5_digit(): void
    {
        AcademicYear::create(['name' => '2026/2027'])->activate();
        $this->fakeInduk([$this->s(101, '2425.10.001'), $this->s(102, '2425.10.002', 'Sari')], [$this->s(201, '99999.1', 'Kelas Dua')]);
        $this->admin()->post('/superadmin/kelas/tarik');
        $this->post('/superadmin/kelas/'.$this->kelas(1)->id.'/tarik')->assertSessionHas('success');

        $budi = User::where('external_id', '101')->first();
        $this->assertSame('24251', $budi->username);
        $this->assertSame('siswa', $budi->role);
        $this->assertTrue($budi->must_change_password);
        $this->assertTrue(Hash::check('24251', $budi->password));
        $this->assertNull(User::where('external_id', '102')->first());   // bentrok username -> dilewati
        $this->assertNull(User::where('external_id', '201')->first());   // kelas lain belum ditarik

        // Budi naik kelas; password yang sudah diganti tidak disentuh.
        $budi->forceFill(['password' => Hash::make('rahasia-baru'), 'must_change_password' => false])->save();
        $this->fakeInduk([], [$this->s(101, '2425.10.001')]);
        $this->post('/superadmin/kelas/'.$this->kelas(2)->id.'/tarik');
        $aktif = ClassMember::where('student_id', $budi->id)->whereNull('left_at')->get();
        $this->assertCount(1, $aktif);
        $this->assertSame('XI TSM 1', $aktif[0]->schoolClass->name);
        $this->assertTrue(Hash::check('rahasia-baru', $budi->fresh()->password));
        $this->assertFalse($budi->fresh()->must_change_password);
    }

    public function test_gagal_api_tidak_mengubah_kelas_dan_respons_kosong_tidak_mengosongkan_kelas(): void
    {
        AcademicYear::create(['name' => '2026/2027'])->activate();
        $this->fakeInduk([$this->s(101, '11111.1')]);
        $this->admin()->post('/superadmin/kelas/tarik');
        $k = $this->kelas(1);
        $this->post("/superadmin/kelas/{$k->id}/tarik");
        $budi = User::where('external_id', '101')->first();

        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['induk.test/api/siswa/1' => Http::response('x', 500)]);
        $this->post("/superadmin/kelas/{$k->id}/tarik")->assertSessionHasErrors('sync');
        $this->assertTrue($budi->fresh()->is_active);
        $this->assertSame(1, ClassMember::where('class_id', $k->id)->whereNull('left_at')->count());

        $this->fakeInduk([]);
        $this->post("/superadmin/kelas/{$k->id}/tarik")->assertSessionHasErrors('sync');
        $this->assertSame(1, ClassMember::where('class_id', $k->id)->whereNull('left_at')->count());
    }

    public function test_siswa_keluar_dari_kelas_tetap_aktif_dan_bentrok_tidak_mengubah_akun(): void
    {
        AcademicYear::create(['name' => '2026/2027'])->activate();
        $this->fakeInduk([$this->s(101, '11111.1'), $this->s(102, '11111.2', 'Kembar')]);
        $this->admin()->post('/superadmin/kelas/tarik');
        $k = $this->kelas(1);
        $this->post("/superadmin/kelas/{$k->id}/tarik");
        $a = User::where('external_id', '101')->first();
        $this->assertNull(User::where('external_id', '102')->first());

        $this->fakeInduk([$this->s(555, '55555.1', 'Lain')]);
        $this->post("/superadmin/kelas/{$k->id}/tarik");
        $this->assertTrue($a->fresh()->is_active);
        $this->assertSame(0, ClassMember::where('student_id', $a->id)->whereNull('left_at')->count());
    }

    public function test_halaman_siswa_dan_guru_terpisah(): void
    {
        $this->user('guru', 'guru0001');
        $this->admin()->get('/superadmin/siswa')->assertOk();
        $this->get('/superadmin/guru')->assertOk();
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

    public function test_guru_tidak_bisa_reset_atau_tarik(): void
    {
        $siswa = $this->user('siswa', '12345');
        $this->actingAs($this->user('guru', 'guru0001'))->post("/superadmin/akun/{$siswa->id}/reset")->assertForbidden();
        $this->post('/superadmin/kelas/tarik')->assertForbidden();
        $this->get('/superadmin/siswa')->assertForbidden();
    }

    public function test_siswa_tidak_dobel_dilacak_dari_nis_walau_id_induk_berubah(): void
    {
        AcademicYear::create(['name' => '2026/2027'])->activate();
        $this->fakeInduk([$this->s(101, '2425.10.001')]);
        $this->admin()->post('/superadmin/kelas/tarik');
        $this->post('/superadmin/kelas/'.$this->kelas(1)->id.'/tarik');
        $this->assertSame(1, User::where('role', 'siswa')->count());

        // NIS sama, id induk beda (data induk dibuat ulang) -> akun lama dipakai, bukan dibuat baru
        $this->fakeInduk([$this->s(777, '2425.10.001')]);
        $this->post('/superadmin/kelas/'.$this->kelas(1)->id.'/tarik')->assertSessionHas('success');
        $this->assertSame(1, User::where('role', 'siswa')->count());
        $this->assertSame('777', User::where('role', 'siswa')->first()->external_id);

        // Pindah kelas: tetap satu akun, hanya satu keanggotaan aktif
        $this->fakeInduk([], [$this->s(777, '2425.10.001')]);
        $this->post('/superadmin/kelas/'.$this->kelas(2)->id.'/tarik');
        $this->assertSame(1, User::where('role', 'siswa')->count());
        $this->assertSame(1, ClassMember::whereNull('left_at')->count());
    }
}

<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassMember;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LmsStage4Test extends TestCase
{
    use RefreshDatabase;

    private function u(string $role, string $username): User
    {
        $u = (new User)->forceFill(['name' => ucfirst($username), 'username' => $username, 'role' => $role,
            'password' => bcrypt('x'), 'is_active' => true, 'must_change_password' => false]);
        $u->save();

        return $u;
    }

    private function d(array $over = []): array
    {
        $year = AcademicYear::create(['name' => '2026/2027']);
        $year->forceFill(['is_active' => true])->save();
        $k = SchoolClass::create(['external_id' => 'k1', 'name' => 'X TSM 1', 'is_active' => true]);
        $k2 = SchoolClass::create(['external_id' => 'k2', 'name' => 'XI TSM 1', 'is_active' => true]);
        $guru = $this->u('guru', '1111111111111111');
        $guru2 = $this->u('guru', '2222222222222222');
        $a = $this->u('siswa', '10001');
        $b = $this->u('siswa', '20002');
        ClassMember::create(['class_id' => $k->id, 'student_id' => $a->id, 'academic_year_id' => $year->id]);
        ClassMember::create(['class_id' => $k2->id, 'student_id' => $b->id, 'academic_year_id' => $year->id]);
        $s = AttendanceSession::create(array_merge([
            'teacher_id' => $guru->id, 'academic_year_id' => $year->id, 'title' => 'Bengkel',
            'opens_at' => now()->subHour(), 'closes_at' => now()->addHour(),
        ], $over));
        $s->classes()->sync([$k->id]);

        return compact('year', 'k', 'guru', 'guru2', 'a', 'b', 's');
    }

    public function test_siswa_mengisi_hadir_sendiri(): void
    {
        $d = $this->d();
        $this->actingAs($d['a'])->post('/belajar/presensi/'.$d['s']->id, ['status' => 'hadir'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('lms_attendance_records', ['session_id' => $d['s']->id, 'student_id' => $d['a']->id, 'status' => 'hadir', 'marked_by' => 'self']);
    }

    public function test_siswa_rombel_lain_ditolak(): void
    {
        $d = $this->d();
        $this->actingAs($d['b'])->post('/belajar/presensi/'.$d['s']->id, ['status' => 'hadir'])->assertForbidden();
        $this->assertDatabaseCount('lms_attendance_records', 0);
    }

    public function test_di_luar_jendela_waktu_ditolak(): void
    {
        $d = $this->d(['opens_at' => now()->addHour(), 'closes_at' => now()->addHours(2)]);
        $this->actingAs($d['a'])->post('/belajar/presensi/'.$d['s']->id, ['status' => 'hadir'])->assertSessionHasErrors('status');
        $d['s']->update(['opens_at' => now()->subHours(3), 'closes_at' => now()->subHour()]);
        $this->post('/belajar/presensi/'.$d['s']->id, ['status' => 'hadir'])->assertSessionHasErrors('status');
        $this->assertDatabaseCount('lms_attendance_records', 0);
    }

    public function test_terlambat_dan_izin_butuh_keterangan(): void
    {
        $d = $this->d(['late_after' => now()->subMinutes(10)]);
        $this->actingAs($d['a'])->post('/belajar/presensi/'.$d['s']->id, ['status' => 'hadir']);
        $this->assertDatabaseHas('lms_attendance_records', ['student_id' => $d['a']->id, 'status' => 'terlambat']);
        $this->post('/belajar/presensi/'.$d['s']->id, ['status' => 'izin', 'note' => 'Acara keluarga'])->assertSessionHasErrors('status');
        $this->assertDatabaseHas('lms_attendance_records', ['student_id' => $d['a']->id, 'status' => 'terlambat']);
        $this->assertDatabaseCount('lms_attendance_records', 1);
    }

    public function test_izin_boleh_diubah_ke_hadir_tapi_tidak_sebaliknya(): void
    {
        $d = $this->d();
        $this->actingAs($d['a'])->post('/belajar/presensi/'.$d['s']->id, ['status' => 'izin'])->assertSessionHasErrors('note');
        $this->post('/belajar/presensi/'.$d['s']->id, ['status' => 'izin', 'note' => 'Acara keluarga']);
        $this->assertDatabaseHas('lms_attendance_records', ['student_id' => $d['a']->id, 'status' => 'izin']);
        $this->post('/belajar/presensi/'.$d['s']->id, ['status' => 'hadir']);
        $this->assertDatabaseHas('lms_attendance_records', ['student_id' => $d['a']->id, 'status' => 'hadir']);
        $this->post('/belajar/presensi/'.$d['s']->id, ['status' => 'sakit', 'note' => 'x'])->assertSessionHasErrors('status');
    }

    public function test_status_alpa_tidak_bisa_dipilih_siswa(): void
    {
        $d = $this->d();
        $this->actingAs($d['a'])->post('/belajar/presensi/'.$d['s']->id, ['status' => 'alpa'])->assertSessionHasErrors('status');
        $this->post('/belajar/presensi/'.$d['s']->id, ['status' => 'terlambat'])->assertSessionHasErrors('status');
    }

    public function test_kode_presensi(): void
    {
        $d = $this->d(['code_hash' => Hash::make('ABC123')]);
        $this->actingAs($d['a'])->post('/belajar/presensi/'.$d['s']->id, ['status' => 'hadir', 'code' => 'salah'])->assertSessionHasErrors('code');
        $this->assertDatabaseCount('lms_attendance_records', 0);
        $this->post('/belajar/presensi/'.$d['s']->id, ['status' => 'hadir', 'code' => 'ABC123'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('lms_attendance_records', 1);
        // kode hash tidak bocor ke halaman
        $this->get('/belajar/presensi')->assertDontSee('code_hash')->assertDontSee('ABC123')->assertDontSee('$2y$');
    }

    public function test_kode_salah_berulang_dikunci(): void
    {
        $d = $this->d(['code_hash' => Hash::make('ABC123')]);
        $this->actingAs($d['a']);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/belajar/presensi/'.$d['s']->id, ['status' => 'hadir', 'code' => 'salah'.$i]);
        }
        $this->post('/belajar/presensi/'.$d['s']->id, ['status' => 'hadir', 'code' => 'ABC123'])->assertSessionHasErrors('code');
        $this->assertDatabaseCount('lms_attendance_records', 0);
    }

    public function test_koreksi_guru_mengunci_dari_siswa(): void
    {
        $d = $this->d();
        $this->actingAs($d['guru'])->post('/ruang-guru/presensi/'.$d['s']->id.'/siswa/'.$d['a']->id, ['status' => 'sakit', 'note' => 'Surat dokter'])->assertSessionHasNoErrors();
        $this->actingAs($d['a'])->post('/belajar/presensi/'.$d['s']->id, ['status' => 'hadir'])->assertSessionHasErrors('status');
        $this->assertSame('sakit', AttendanceRecord::first()->status);
    }

    public function test_guru_lain_tidak_bisa_mengakses(): void
    {
        $d = $this->d();
        $this->actingAs($d['guru2']);
        $id = $d['s']->id;
        $this->get("/ruang-guru/presensi/$id")->assertForbidden();
        $this->put("/ruang-guru/presensi/$id", [])->assertForbidden();
        $this->delete("/ruang-guru/presensi/$id")->assertForbidden();
        $this->get("/ruang-guru/presensi/$id/ekspor")->assertForbidden();
        $this->post("/ruang-guru/presensi/$id/siswa/".$d['a']->id, ['status' => 'hadir'])->assertForbidden();
        $this->actingAs($d['a'])->get("/ruang-guru/presensi/$id")->assertForbidden();
    }

    public function test_guru_tidak_bisa_set_siswa_di_luar_peserta(): void
    {
        $d = $this->d();
        $this->actingAs($d['guru'])->post('/ruang-guru/presensi/'.$d['s']->id.'/siswa/'.$d['b']->id, ['status' => 'hadir'])->assertNotFound();
    }

    public function test_guru_membuat_sesi_dan_zona_waktu_wib(): void
    {
        $d = $this->d();
        $this->actingAs($d['guru'])->post('/ruang-guru/presensi', [
            'title' => 'Praktik', 'opens_at' => '2026-11-02T07:00', 'closes_at' => '2026-11-02T09:00',
            'class_ids' => [$d['k']->id], 'code' => 'KODE1',
        ])->assertSessionHasNoErrors();
        $s = AttendanceSession::where('title', 'Praktik')->first();
        $this->assertSame('2026-11-02 00:00:00', $s->opens_at->format('Y-m-d H:i:s'));   // 07:00 WIB = 00:00 UTC
        $this->assertNotNull($s->code_hash);
        $this->assertNotSame('KODE1', $s->code_hash);
        $this->post('/ruang-guru/presensi', ['title' => 'x', 'opens_at' => '2026-11-02T09:00', 'closes_at' => '2026-11-02T07:00', 'class_ids' => [$d['k']->id]])
            ->assertSessionHasErrors('closes_at');
    }

    public function test_ekspor_csv_menetralkan_rumus(): void
    {
        $d = $this->d();
        $d['a']->forceFill(['name' => '=HYPERLINK("x")'])->save();
        $res = $this->actingAs($d['guru'])->get('/ruang-guru/presensi/'.$d['s']->id.'/ekspor');
        $res->assertOk();
        $this->assertStringNotContainsString('"=HYPERLINK', $res->getContent());
        $this->assertStringContainsString("\"'=HYPERLINK", $res->getContent());
        $d['a']->forceFill(['name' => ' =cmd|x'])->save();
        $res = $this->get('/ruang-guru/presensi/'.$d['s']->id.'/ekspor');
        $this->assertStringContainsString("\"' =cmd|x", $res->getContent());
    }
}

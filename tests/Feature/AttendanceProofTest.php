<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceProofTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $n): User
    {
        $u = new User;
        $u->forceFill(['name' => $n, 'username' => $n, 'role' => $role, 'password' => Hash::make('x'), 'is_active' => true, 'must_change_password' => false])->save();

        return $u;
    }

    private function prep(bool $proof, string $suffix = '1'): array
    {
        Storage::fake('local');
        $year = AcademicYear::active() ?? AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $class = SchoolClass::create(['external_id' => 'x'.$suffix, 'name' => 'X TSM '.$suffix, 'is_active' => true]);
        $guru = $this->user('guru', 'g'.$suffix);
        $siswa = $this->user('siswa', 's'.$suffix);
        DB::table('core_class_members')->insert(['class_id' => $class->id, 'student_id' => $siswa->id, 'academic_year_id' => $year->id, 'created_at' => now(), 'updated_at' => now()]);
        $s = AttendanceSession::create(['teacher_id' => $guru->id, 'academic_year_id' => $year->id, 'title' => 'Daring', 'opens_at' => now()->subHour(), 'closes_at' => now()->addHour(), 'require_proof' => $proof]);
        $s->classes()->sync([$class->id]);

        return [$guru, $siswa, $s];
    }

    public function test_hadir_tanpa_selfie_atau_lokasi_ditolak(): void
    {
        [, $siswa, $s] = $this->prep(true);
        $this->actingAs($siswa)->post("/belajar/presensi/{$s->id}", ['status' => 'hadir'])->assertSessionHasErrors('selfie');
        $this->actingAs($siswa)->post("/belajar/presensi/{$s->id}", ['status' => 'hadir', 'selfie' => UploadedFile::fake()->image('a.jpg')])->assertSessionHasErrors('selfie');
        $this->assertSame(0, AttendanceRecord::count());
    }

    public function test_selfie_dan_lokasi_tersimpan_hanya_guru_pemilik_bisa_lihat(): void
    {
        [$guru, $siswa, $s] = $this->prep(true);
        $this->actingAs($siswa)->post("/belajar/presensi/{$s->id}", [
            'status' => 'hadir', 'selfie' => UploadedFile::fake()->image('a.jpg', 400, 300), 'lat' => -8.0954, 'lng' => 112.1609, 'accuracy' => 23.4,
        ])->assertSessionHasNoErrors();
        $r = AttendanceRecord::first();
        $this->assertSame('hadir', $r->status);
        $this->assertNotNull($r->photo_path);
        $this->assertSame(23, $r->accuracy);
        Storage::disk('local')->assertExists($r->photo_path);

        $this->actingAs($guru)->get("/ruang-guru/presensi/{$s->id}/foto/{$siswa->id}")->assertOk();
        $this->actingAs($this->user('guru', 'lain'))->get("/ruang-guru/presensi/{$s->id}/foto/{$siswa->id}")->assertForbidden();
        $this->actingAs($siswa)->get("/ruang-guru/presensi/{$s->id}/foto/{$siswa->id}")->assertForbidden();
    }

    public function test_sesi_biasa_dan_izin_tidak_butuh_bukti(): void
    {
        [, $siswa, $s] = $this->prep(false);
        $this->actingAs($siswa)->post("/belajar/presensi/{$s->id}", ['status' => 'hadir'])->assertSessionHasNoErrors();
        $this->assertNull(AttendanceRecord::first()->photo_path);

        [, $s2, $x] = $this->prep(true, '2');
        $this->actingAs($s2)->post("/belajar/presensi/{$x->id}", ['status' => 'sakit', 'note' => 'demam'])->assertSessionHasNoErrors();
    }

    public function test_pruner_hapus_foto_dan_koordinat_setelah_semester(): void
    {
        [, $siswa, $s] = $this->prep(true);
        Storage::disk('local')->put('attendance/1/x.jpg', 'x');
        AttendanceRecord::create(['session_id' => $s->id, 'student_id' => $siswa->id, 'status' => 'hadir', 'marked_at' => now()->subDays(200), 'photo_path' => 'attendance/1/x.jpg', 'lat' => 1, 'lng' => 1, 'accuracy' => 5]);
        $this->artisan('attendance:prune-proof')->assertSuccessful();
        $r = AttendanceRecord::first();
        $this->assertNull($r->photo_path);
        $this->assertNull($r->lat);
        $this->assertSame('hadir', $r->status);
        Storage::disk('local')->assertMissing('attendance/1/x.jpg');
    }
}

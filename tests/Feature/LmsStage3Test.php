<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LmsStage3Test extends TestCase
{
    use RefreshDatabase;

    private function u(string $role, string $username): User
    {
        $user = (new User)->forceFill([
            'name' => ucfirst($username), 'username' => $username, 'role' => $role,
            'password' => bcrypt('x'), 'is_active' => true, 'must_change_password' => false,
        ]);
        $user->save();

        return $user;
    }

    private function setup3(): array
    {
        $year = AcademicYear::create(['name' => '2026/2027']);
        $year->forceFill(['is_active' => true])->save();
        $kelas = SchoolClass::create(['external_id' => 'k1', 'name' => 'X TSM 1', 'is_active' => true]);
        $other = SchoolClass::create(['external_id' => 'k2', 'name' => 'XI TSM 1', 'is_active' => true]);
        $guru = $this->u('guru', '1111111111111111');
        $guru2 = $this->u('guru', '2222222222222222');
        $siswa = $this->u('siswa', '10001');
        $luar = $this->u('siswa', '20002');
        ClassMember::create(['class_id' => $kelas->id, 'student_id' => $siswa->id, 'academic_year_id' => $year->id]);
        ClassMember::create(['class_id' => $other->id, 'student_id' => $luar->id, 'academic_year_id' => $year->id]);
        $course = Course::create(['teacher_id' => $guru->id, 'academic_year_id' => $year->id, 'title' => 'Mesin']);
        $course->classes()->sync([$kelas->id]);
        $sec = $course->sections()->create(['title' => 'Bab 1', 'is_published' => true]);
        $les = Lesson::create(['section_id' => $sec->id, 'type' => 'reading', 'title' => 'Pengantar', 'content' => '<b>x</b>', 'is_published' => true]);

        return compact('year', 'kelas', 'guru', 'guru2', 'siswa', 'luar', 'course', 'sec', 'les');
    }

    public function test_video_url_hanya_youtube_dan_drive(): void
    {
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', Lesson::normalizeVideoUrl('https://youtu.be/dQw4w9WgXcQ'));
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', Lesson::normalizeVideoUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=3'));
        $this->assertStringContainsString('/preview', Lesson::normalizeVideoUrl('https://drive.google.com/file/d/abcdefghij123/view'));
        $this->assertNull(Lesson::normalizeVideoUrl('http://youtu.be/dQw4w9WgXcQ'));
        $this->assertNull(Lesson::normalizeVideoUrl('https://evil.com/watch?v=dQw4w9WgXcQ'));
        $this->assertNull(Lesson::normalizeVideoUrl('https://youtube.com.evil.com/watch?v=dQw4w9WgXcQ'));
        $this->assertNull(Lesson::normalizeVideoUrl('javascript:alert(1)'));
    }

    public function test_siswa_hanya_melihat_mapel_rombelnya(): void
    {
        $d = $this->setup3();
        $this->actingAs($d['siswa'])->get('/belajar/mapel/'.$d['course']->id)->assertOk();
        $this->actingAs($d['luar'])->get('/belajar/mapel/'.$d['course']->id)->assertForbidden();
        $this->actingAs($d['luar'])->get('/belajar/materi/'.$d['les']->id)->assertForbidden();
    }

    public function test_materi_draf_tidak_bisa_dibuka_siswa(): void
    {
        $d = $this->setup3();
        $d['les']->update(['is_published' => false]);
        $this->actingAs($d['siswa'])->get('/belajar/materi/'.$d['les']->id)->assertNotFound();
        $d['les']->update(['is_published' => true]);
        $d['sec']->update(['is_published' => false]);
        $this->actingAs($d['siswa'])->get('/belajar/materi/'.$d['les']->id)->assertNotFound();
    }

    public function test_siswa_pindah_rombel_kehilangan_akses(): void
    {
        $d = $this->setup3();
        ClassMember::where('student_id', $d['siswa']->id)->update(['left_at' => now()]);
        $this->actingAs($d['siswa'])->get('/belajar/mapel/'.$d['course']->id)->assertForbidden();
    }

    public function test_tandai_selesai_idempoten(): void
    {
        $d = $this->setup3();
        $this->actingAs($d['siswa']);
        $this->post('/belajar/materi/'.$d['les']->id.'/selesai')->assertRedirect();
        $this->post('/belajar/materi/'.$d['les']->id.'/selesai')->assertRedirect();
        $this->assertDatabaseCount('lms_lesson_progress', 1);
        $this->post('/belajar/materi/'.$d['les']->id.'/selesai');
    }

    public function test_guru_lain_tidak_bisa_mengelola_mapel(): void
    {
        $d = $this->setup3();
        $this->actingAs($d['guru2']);
        $this->get('/ruang-guru/mapel/'.$d['course']->id)->assertForbidden();
        $this->post('/ruang-guru/mapel/'.$d['course']->id.'/bab', ['title' => 'x'])->assertForbidden();
        $this->post('/ruang-guru/materi/'.$d['les']->id, ['type' => 'reading', 'title' => 'x'])->assertForbidden();
        $this->delete('/ruang-guru/materi/'.$d['les']->id)->assertForbidden();
        $this->delete('/ruang-guru/bab/'.$d['sec']->id)->assertForbidden();
        $this->put('/ruang-guru/bab/'.$d['sec']->id, ['title' => 'x'])->assertForbidden();
        $this->post('/ruang-guru/bab/'.$d['sec']->id.'/geser', ['dir' => 'up'])->assertForbidden();
        $this->post('/ruang-guru/bab/'.$d['sec']->id.'/materi', ['type' => 'reading', 'title' => 'x'])->assertForbidden();
        $this->get('/ruang-guru/mapel/'.$d['course']->id.'/progres')->assertForbidden();
        $this->assertDatabaseHas('lms_lessons', ['id' => $d['les']->id]);
    }

    public function test_siswa_tidak_bisa_masuk_area_guru_dan_superadmin(): void
    {
        $d = $this->setup3();
        $this->actingAs($d['siswa'])->post('/ruang-guru/mapel/'.$d['course']->id.'/bab', ['title' => 'x'])->assertForbidden();
        $this->get('/superadmin/mapel')->assertForbidden();
        $this->actingAs($d['guru'])->get('/superadmin/mapel')->assertForbidden();
    }

    public function test_guru_tambah_materi_video_dan_tolak_url_asing(): void
    {
        $d = $this->setup3();
        $this->actingAs($d['guru']);
        $this->post('/ruang-guru/bab/'.$d['sec']->id.'/materi', ['type' => 'video', 'title' => 'V', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ'])->assertRedirect();
        $this->assertDatabaseHas('lms_lessons', ['title' => 'V', 'video_url' => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ']);
        $this->post('/ruang-guru/bab/'.$d['sec']->id.'/materi', ['type' => 'video', 'title' => 'Jahat', 'video_url' => 'https://evil.com/x'])->assertSessionHasErrors('video_url');
        $this->assertDatabaseMissing('lms_lessons', ['title' => 'Jahat']);
    }

    public function test_berkas_materi_hanya_untuk_yang_berhak_dan_tipe_dibatasi(): void
    {
        Storage::fake('local');
        $d = $this->setup3();
        $this->actingAs($d['guru']);
        $this->post('/ruang-guru/bab/'.$d['sec']->id.'/materi', ['type' => 'file', 'title' => 'F', 'is_published' => true,
            'file' => UploadedFile::fake()->create('a.php', 10)])->assertSessionHasErrors('file');
        $this->post('/ruang-guru/bab/'.$d['sec']->id.'/materi', ['type' => 'file', 'title' => 'F', 'is_published' => true,
            'file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])->assertRedirect();
        $l = Lesson::where('title', 'F')->first();
        Storage::disk('local')->assertExists($l->attachment_path);

        $this->actingAs($d['siswa'])->get('/berkas-materi/'.$l->id)->assertOk();
        $this->actingAs($d['luar'])->get('/berkas-materi/'.$l->id)->assertForbidden();
        $this->actingAs($d['guru2'])->get('/berkas-materi/'.$l->id)->assertForbidden();
        $l->update(['is_published' => false]);
        $this->actingAs($d['siswa'])->get('/berkas-materi/'.$l->id)->assertNotFound();
        $l->update(['is_published' => true]);
        // path tidak bocor ke halaman siswa
        $this->actingAs($d['siswa'])->get('/belajar/materi/'.$l->id)->assertDontSee('lms/lessons');
    }

    public function test_superadmin_membuat_mapel_dengan_guru_dan_rombel(): void
    {
        $d = $this->setup3();
        $sa = $this->u('superadmin', 'root0001');
        $this->actingAs($sa)->post('/superadmin/mapel', [
            'title' => 'Kelistrikan', 'teacher_id' => $d['guru']->id, 'class_ids' => [$d['kelas']->id],
        ])->assertRedirect();
        $this->assertDatabaseHas('lms_courses', ['title' => 'Kelistrikan', 'academic_year_id' => $d['year']->id]);
        // siswa tidak boleh jadi pengajar
        $this->post('/superadmin/mapel', ['title' => 'X', 'teacher_id' => $d['siswa']->id, 'class_ids' => [$d['kelas']->id]])
            ->assertSessionHasErrors('teacher_id');
    }
}

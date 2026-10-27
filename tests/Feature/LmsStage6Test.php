<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\ClassMember;
use App\Models\Course;
use App\Models\SchoolClass;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LmsStage6Test extends TestCase
{
    use RefreshDatabase;

    private function u(string $role, string $username): User
    {
        $u = (new User)->forceFill(['name' => ucfirst($username), 'username' => $username, 'role' => $role,
            'password' => bcrypt('x'), 'is_active' => true, 'must_change_password' => false]);
        $u->save();

        return $u;
    }

    private function d(): array
    {
        Storage::fake('local');
        $year = AcademicYear::create(['name' => '2026/2027']);
        $year->forceFill(['is_active' => true])->save();
        $k = SchoolClass::create(['external_id' => 'k1', 'name' => 'X TSM 1', 'is_active' => true]);
        $guru = $this->u('guru', '1111111111111111');
        $guru2 = $this->u('guru', '2222222222222222');
        $s = $this->u('siswa', '10001');
        $luar = $this->u('siswa', '20002');
        ClassMember::create(['class_id' => $k->id, 'student_id' => $s->id, 'academic_year_id' => $year->id]);
        $c = Course::create(['teacher_id' => $guru->id, 'academic_year_id' => $year->id, 'title' => 'Mesin']);
        $c->classes()->sync([$k->id]);

        return compact('guru', 'guru2', 's', 'luar', 'c', 'year');
    }

    private function tugas(Course $c, array $over = []): Assignment
    {
        return Assignment::create(array_merge([
            'course_id' => $c->id, 'title' => 'Servis rem', 'instructions' => 'Jelaskan langkahnya.',
            'due_at' => now()->addDay(), 'allow_late' => true, 'max_score' => 100, 'is_published' => true,
        ], $over));
    }

    private function png(): UploadedFile
    {
        // PNG 1x1 valid supaya deteksi MIME & getimagesize lolos.
        $raw = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8AAAwAB/AF/6Oj8WQAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent('foto.png', $raw);
    }

    public function test_guru_membuat_tugas_dengan_rubrik(): void
    {
        $d = $this->d();
        $this->actingAs($d['guru'])->post("/ruang-guru/mapel/{$d['c']->id}/tugas", [
            'title' => 'Servis rem', 'instructions' => 'Tulis langkah', 'due_at' => '2026-11-01T10:00',
            'allow_late' => true, 'max_score' => 100, 'is_published' => false,
            'criteria' => [['name' => 'Ketepatan', 'weight' => 2], ['name' => 'Kerapian', 'weight' => 1]],
        ])->assertRedirect();

        $a = Assignment::firstOrFail();
        $this->assertSame('Servis rem', $a->title);
        $this->assertFalse($a->is_published);
        $this->assertSame(2, $a->criteria()->count());

        $props = $this->actingAs($d['guru'])->get("/ruang-guru/mapel/{$d['c']->id}/tugas")
            ->viewData('page')['props'];
        $this->assertCount(1, $props['assignments']);
        $this->assertCount(2, $props['assignments'][0]['criteria']);
    }

    public function test_guru_lain_tidak_boleh_mengelola_tugas(): void
    {
        $d = $this->d();
        $a = $this->tugas($d['c']);

        $this->actingAs($d['guru2'])->get("/ruang-guru/tugas/{$a->id}")->assertForbidden();
        $this->actingAs($d['guru2'])->put("/ruang-guru/tugas/{$a->id}", ['title' => 'X', 'max_score' => 10])->assertForbidden();
        $this->actingAs($d['guru2'])->delete("/ruang-guru/tugas/{$a->id}")->assertForbidden();
        $this->actingAs($d['guru2'])->get("/ruang-guru/mapel/{$d['c']->id}/buku-nilai")->assertForbidden();
        $this->actingAs($d['guru2'])->get("/ruang-guru/mapel/{$d['c']->id}/buku-nilai/ekspor")->assertForbidden();
    }

    public function test_siswa_non_anggota_tidak_bisa_melihat_tugas(): void
    {
        $d = $this->d();
        $a = $this->tugas($d['c']);

        $this->actingAs($d['luar'])->get("/belajar/tugas/{$a->id}")->assertNotFound();
        $this->actingAs($d['luar'])->post("/belajar/tugas/{$a->id}", ['type' => 'text', 'text' => 'halo'])->assertNotFound();
    }

    public function test_tugas_draf_tidak_tampil_ke_siswa(): void
    {
        $d = $this->d();
        $a = $this->tugas($d['c'], ['is_published' => false]);

        $this->actingAs($d['s'])->get("/belajar/tugas/{$a->id}")->assertNotFound();
        $props = $this->actingAs($d['s'])->get("/belajar/mapel/{$d['c']->id}")->viewData('page')['props'];
        $this->assertCount(0, $props['assignments']);
    }

    public function test_siswa_mengumpulkan_teks_lalu_guru_menilai(): void
    {
        $d = $this->d();
        $a = $this->tugas($d['c']);

        $this->actingAs($d['s'])->post("/belajar/tugas/{$a->id}", ['type' => 'text', 'text' => 'Langkah 1 buka baut'])
            ->assertRedirect("/belajar/tugas/{$a->id}");

        $sub = Submission::firstOrFail();
        $this->assertSame('text', $sub->type);
        $this->assertFalse($sub->is_late);

        $this->actingAs($d['guru'])->post("/ruang-guru/pengumpulan/{$sub->id}/nilai", [
            'score' => 85, 'feedback' => 'Bagus', 'rubric_scores' => [],
        ])->assertRedirect();
        $sub->refresh();
        $this->assertSame(85.0, (float) $sub->score);
        $this->assertNotNull($sub->graded_at);

        $props = $this->actingAs($d['s'])->get("/belajar/tugas/{$a->id}")->viewData('page')['props'];
        $this->assertTrue($props['submission']['graded']);
        $this->assertSame(85.0, $props['submission']['score']);

        // Sudah dinilai -> siswa tidak bisa mengubah lagi.
        $this->actingAs($d['s'])->post("/belajar/tugas/{$a->id}", ['type' => 'text', 'text' => 'revisi'])->assertForbidden();
    }

    public function test_nilai_di_luar_batas_maksimal_ditolak(): void
    {
        $d = $this->d();
        $a = $this->tugas($d['c'], ['max_score' => 50]);
        $this->actingAs($d['s'])->post("/belajar/tugas/{$a->id}", ['type' => 'text', 'text' => 'ok'])->assertRedirect();
        $sub = Submission::firstOrFail();

        $this->actingAs($d['guru'])->post("/ruang-guru/pengumpulan/{$sub->id}/nilai", ['score' => 51])
            ->assertSessionHasErrors('score');
        $this->actingAs($d['guru'])->post("/ruang-guru/pengumpulan/{$sub->id}/nilai", ['score' => -1])
            ->assertSessionHasErrors('score');
    }

    public function test_terlambat_ditolak_bila_allow_late_mati(): void
    {
        $d = $this->d();
        $mati = $this->tugas($d['c'], ['due_at' => now()->subHour(), 'allow_late' => false]);
        $buka = $this->tugas($d['c'], ['title' => 'Tugas 2', 'due_at' => now()->subHour(), 'allow_late' => true]);

        $this->actingAs($d['s'])->post("/belajar/tugas/{$mati->id}", ['type' => 'text', 'text' => 'telat'])->assertForbidden();
        $this->actingAs($d['s'])->post("/belajar/tugas/{$buka->id}", ['type' => 'text', 'text' => 'telat'])->assertRedirect();
        $this->assertTrue(Submission::firstOrFail()->is_late);
    }

    public function test_unggah_pdf_valid_tersimpan_di_disk_privat(): void
    {
        $d = $this->d();
        $a = $this->tugas($d['c']);
        $tmp = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($tmp, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n");
        $pdf = new UploadedFile($tmp, 'jawaban.pdf', 'application/pdf', null, true);

        $this->actingAs($d['s'])->post("/belajar/tugas/{$a->id}", ['type' => 'file', 'file' => $pdf])->assertRedirect();

        $sub = Submission::firstOrFail();
        $this->assertSame('file', $sub->type);
        $this->assertNotNull($sub->file_path);
        Storage::disk('local')->assertExists($sub->file_path);

        // Guru pemilik mapel boleh mengunduh; guru lain tidak.
        $file = SubmissionFile::firstOrFail();
        $this->actingAs($d['guru'])->get("/ruang-guru/berkas-pengumpulan/{$file->id}")->assertOk();
        $this->actingAs($d['guru2'])->get("/ruang-guru/berkas-pengumpulan/{$file->id}")->assertForbidden();
        // Siswa lain tidak boleh mengunduh berkas milik temannya.
        $this->actingAs($d['luar'])->get("/belajar/tugas-berkas/{$file->id}")->assertNotFound();
    }

    public function test_tipe_berkas_berbahaya_ditolak(): void
    {
        $d = $this->d();
        $a = $this->tugas($d['c']);
        // Berkas asli (bukan fake) supaya MIME dideteksi dari isi, bukan dari nama.
        $tmp = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($tmp, "<?php echo 'x'; ?>");
        $jahat = new UploadedFile($tmp, 'shell.pdf', 'application/pdf', null, true);

        $this->actingAs($d['s'])->post("/belajar/tugas/{$a->id}", ['type' => 'file', 'file' => $jahat])
            ->assertSessionHasErrors('file');
        $this->assertSame(0, Submission::count());
    }

    public function test_berkas_lebih_dari_5mb_ditolak(): void
    {
        $d = $this->d();
        $a = $this->tugas($d['c']);
        $besar = UploadedFile::fake()->create('besar.pdf', 6000, 'application/pdf');

        $this->actingAs($d['s'])->post("/belajar/tugas/{$a->id}", ['type' => 'file', 'file' => $besar])
            ->assertSessionHasErrors('file');
        $this->assertSame(0, Submission::count());
    }

    public function test_hapus_lampiran_menyimpan_nilai(): void
    {
        $d = $this->d();
        $a = $this->tugas($d['c']);
        $this->actingAs($d['s'])->post("/belajar/tugas/{$a->id}", ['type' => 'file', 'file' => $this->png()])->assertRedirect();
        $sub = Submission::firstOrFail();
        $path = $sub->file_path;
        Storage::disk('local')->assertExists($path);

        $this->actingAs($d['guru'])->post("/ruang-guru/pengumpulan/{$sub->id}/nilai", ['score' => 90])->assertRedirect();
        $this->actingAs($d['guru'])->delete("/ruang-guru/pengumpulan/{$sub->id}/berkas")->assertRedirect();

        $sub->refresh();
        $this->assertNull($sub->file_path);
        $this->assertSame(90.0, (float) $sub->score);
        Storage::disk('local')->assertMissing($path);
        $this->assertSame(0, SubmissionFile::count());
    }

    public function test_buku_nilai_dan_ekspor_excel(): void
    {
        $d = $this->d();
        $a = $this->tugas($d['c']);
        $this->actingAs($d['s'])->post("/belajar/tugas/{$a->id}", ['type' => 'text', 'text' => 'ok'])->assertRedirect();
        $sub = Submission::firstOrFail();
        $this->actingAs($d['guru'])->post("/ruang-guru/pengumpulan/{$sub->id}/nilai", ['score' => 40])->assertRedirect();

        $props = $this->actingAs($d['guru'])->get("/ruang-guru/mapel/{$d['c']->id}/buku-nilai")->viewData('page')['props'];
        $this->assertCount(1, $props['columns']);
        $this->assertCount(1, $props['rows']);
        $this->assertSame(40.0, $props['rows'][0]['cells'][0]);
        $this->assertSame(40.0, $props['rows'][0]['average']);

        $res = $this->actingAs($d['guru'])->get("/ruang-guru/mapel/{$d['c']->id}/buku-nilai/ekspor");
        $res->assertOk();
        $this->assertStringContainsString('spreadsheetml', (string) $res->headers->get('content-type'));
        $this->assertStringContainsString('attachment', (string) $res->headers->get('content-disposition'));
    }

    public function test_superadmin_melihat_penyimpanan_dan_mengatur_retensi(): void
    {
        $d = $this->d();
        $a = $this->tugas($d['c']);
        $this->actingAs($d['s'])->post("/belajar/tugas/{$a->id}", ['type' => 'file', 'file' => $this->png()])->assertRedirect();
        $sa = $this->u('superadmin', 'root');

        $props = $this->actingAs($sa)->get('/superadmin/penyimpanan')->viewData('page')['props'];
        $this->assertSame(1, $props['count']);
        $this->assertGreaterThan(0, $props['total']);

        $this->actingAs($sa)->put('/superadmin/penyimpanan', ['retention' => 90])->assertRedirect();
        $this->assertSame(90, (int) \App\Models\Setting::get('lms.assignment_file_days', 180));

        $file = SubmissionFile::firstOrFail();
        $this->actingAs($sa)->delete("/superadmin/penyimpanan/{$file->id}")->assertRedirect();
        $this->assertSame(0, SubmissionFile::count());
        $this->assertNull(Submission::firstOrFail()->file_path);
    }

    public function test_siswa_dan_guru_tidak_boleh_masuk_penyimpanan(): void
    {
        $d = $this->d();
        $this->actingAs($d['guru'])->get('/superadmin/penyimpanan')->assertForbidden();
        $this->actingAs($d['s'])->get('/superadmin/penyimpanan')->assertForbidden();
    }

    public function test_hapus_tugas_menghapus_pengumpulan_dan_berkas(): void
    {
        $d = $this->d();
        $a = $this->tugas($d['c']);
        $this->actingAs($d['s'])->post("/belajar/tugas/{$a->id}", ['type' => 'file', 'file' => $this->png()])->assertRedirect();
        $path = Submission::firstOrFail()->file_path;

        $this->actingAs($d['guru'])->delete("/ruang-guru/tugas/{$a->id}")->assertRedirect();
        $this->assertSame(0, Assignment::count());
        $this->assertSame(0, Submission::count());
        $this->assertSame(0, SubmissionFile::count());
        Storage::disk('local')->assertMissing($path);
    }
}

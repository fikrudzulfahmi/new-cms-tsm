<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\QuestionImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LmsStage5Test extends TestCase
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
        $year = AcademicYear::create(['name' => '2026/2027']);
        $year->forceFill(['is_active' => true])->save();
        $k = SchoolClass::create(['external_id' => 'k1', 'name' => 'X TSM 1', 'is_active' => true]);
        $guru = $this->u('guru', '1111111111111111');
        $guru2 = $this->u('guru', '2222222222222222');
        $s = $this->u('siswa', '10001');
        $luar = $this->u('siswa', '20002');
        ClassMember::create(['class_id' => $k->id, 'student_id' => $s->id, 'academic_year_id' => $year->id]);
        $c = Course::create(['teacher_id' => $guru->id, 'academic_year_id' => $year->id, 'title' => 'Mesin', 'leaderboard_enabled' => true]);
        $c->classes()->sync([$k->id]);

        return compact('guru', 'guru2', 's', 'luar', 'c');
    }

    private function mk(Course $c, string $type, array $opts, float $pts = 1, string $body = 'Soal'): Question
    {
        return \App\Http\Controllers\Guru\QuestionController::persist($c, \App\Http\Controllers\Guru\QuestionController::normalize([
            'type' => $type, 'body' => $body, 'points' => $pts, 'options' => $opts,
        ]));
    }

    private function quiz(Course $c, array $qs, array $over = []): Quiz
    {
        $q = Quiz::create(array_merge(['course_id' => $c->id, 'title' => 'UH 1', 'opens_at' => now()->subHour(), 'closes_at' => now()->addHour(),
            'duration_min' => 30, 'max_attempts' => 2, 'is_published' => true, 'shuffle_questions' => false, 'shuffle_options' => false], $over));
        foreach ($qs as $i => $x) {
            $q->questions()->attach($x->id, ['sort_order' => $i, 'points' => $x->points]);
        }

        return $q;
    }

    public function test_grading_all_types(): void
    {
        $d = $this->d();
        $pg = $this->mk($d['c'], 'pg', [['body' => 'A', 'is_correct' => false], ['body' => 'B', 'is_correct' => true]], 2);
        $bs = $this->mk($d['c'], 'benar_salah', [['body' => 'Salah', 'is_correct' => true]], 1);
        $kx = $this->mk($d['c'], 'pg_kompleks', [['body' => 'a', 'is_correct' => true], ['body' => 'b', 'is_correct' => true], ['body' => 'c', 'is_correct' => false]], 2);
        $mj = $this->mk($d['c'], 'menjodohkan', [['body' => 'Busi', 'match_body' => 'Api'], ['body' => 'Oli', 'match_body' => 'Pelumas']], 2);
        $is = $this->mk($d['c'], 'isian', [['body' => 'Piston'], ['body' => 'Torak']], 1);
        $ur = $this->mk($d['c'], 'uraian', [], 2);
        $quiz = $this->quiz($d['c'], [$pg, $bs, $kx, $mj, $is, $ur]);

        $this->actingAs($d['s'])->post("/belajar/quiz/{$quiz->id}/mulai")->assertRedirect();
        $at = QuizAttempt::first();
        $put = fn ($q, $ans) => $this->actingAs($d['s'])->putJson("/belajar/pengerjaan/{$at->id}/soal/{$q->id}", ['answer' => $ans])->assertOk();
        $put($pg, ['option' => $pg->options->firstWhere('is_correct', true)->id]);
        $put($bs, ['option' => $bs->options->firstWhere('is_correct', true)->id]);
        $put($kx, ['options' => [$kx->options[0]->id]]); // setengah benar
        $tk = fn ($o) => \App\Http\Controllers\Siswa\QuizController::rightToken($at->id, $o->id);
        $put($mj, ['pairs' => [$mj->options[0]->id => $tk($mj->options[0]), $mj->options[1]->id => $tk($mj->options[0])]]); // 1 dari 2
        $put($is, ['text' => '  torak ']);
        $put($ur, ['text' => 'jawaban saya']);

        $this->actingAs($d['s'])->post("/belajar/pengerjaan/{$at->id}/kumpul")->assertRedirect();
        $at->refresh();
        $this->assertSame('submitted', $at->status); // uraian belum dinilai
        // 2 + 1 + 1 + 1 + 1 = 6 dari 10 sebelum uraian
        $this->assertEquals(60.0, (float) $at->score);

        $this->actingAs($d['guru'])->post("/ruang-guru/pengerjaan/{$at->id}/soal/{$ur->id}", ['points' => 2, 'feedback' => 'bagus'])->assertSessionHasNoErrors();
        $at->refresh();
        $this->assertSame('graded', $at->status);
        $this->assertEquals(80.0, (float) $at->score);
    }

    public function test_answer_key_never_sent_during_attempt(): void
    {
        $d = $this->d();
        $q = $this->mk($d['c'], 'pg', [['body' => 'A', 'is_correct' => false], ['body' => 'B', 'is_correct' => true]]);
        $quiz = $this->quiz($d['c'], [$q]);
        $this->actingAs($d['s'])->post("/belajar/quiz/{$quiz->id}/mulai");
        $at = QuizAttempt::first();
        $r = $this->actingAs($d['s'])->get("/belajar/pengerjaan/{$at->id}");
        $r->assertOk();
        $this->assertStringNotContainsString('is_correct', $r->getContent());
        $this->assertStringNotContainsString('explanation', $r->getContent());
    }

    public function test_access_control(): void
    {
        $d = $this->d();
        $q = $this->mk($d['c'], 'uraian', []);
        $quiz = $this->quiz($d['c'], [$q]);
        $this->actingAs($d['luar'])->get("/belajar/quiz/{$quiz->id}")->assertNotFound();
        $this->actingAs($d['luar'])->post("/belajar/quiz/{$quiz->id}/mulai")->assertNotFound();
        $this->actingAs($d['guru2'])->get("/ruang-guru/quiz/{$quiz->id}")->assertForbidden();
        $this->actingAs($d['guru2'])->get("/ruang-guru/mapel/{$d['c']->id}/soal")->assertForbidden();
        $this->actingAs($d['guru2'])->delete("/ruang-guru/soal/{$q->id}")->assertForbidden();
        $this->actingAs($d['s'])->get("/ruang-guru/quiz/{$quiz->id}")->assertForbidden();

        $this->actingAs($d['s'])->post("/belajar/quiz/{$quiz->id}/mulai");
        $at = QuizAttempt::first();
        $this->actingAs($d['luar'])->get("/belajar/pengerjaan/{$at->id}")->assertNotFound();
        $this->actingAs($d['luar'])->putJson("/belajar/pengerjaan/{$at->id}/soal/{$q->id}", ['answer' => ['text' => 'x']])->assertNotFound();
        $this->actingAs($d['guru2'])->post("/ruang-guru/pengerjaan/{$at->id}/soal/{$q->id}", ['points' => 1])->assertForbidden();
    }

    public function test_draft_closed_and_code(): void
    {
        $d = $this->d();
        $q = $this->mk($d['c'], 'uraian', []);
        $draft = $this->quiz($d['c'], [$q], ['is_published' => false]);
        $this->actingAs($d['s'])->get("/belajar/quiz/{$draft->id}")->assertNotFound();

        $closed = $this->quiz($d['c'], [$q], ['opens_at' => now()->subDays(2), 'closes_at' => now()->subDay()]);
        $this->actingAs($d['s'])->post("/belajar/quiz/{$closed->id}/mulai")->assertSessionHasErrors('quiz');

        $coded = $this->quiz($d['c'], [$q], ['access_code_hash' => bcrypt('rahasia')]);
        $this->actingAs($d['s'])->post("/belajar/quiz/{$coded->id}/mulai", ['access_code' => 'salah'])->assertSessionHasErrors('access_code');
        $this->assertSame(0, QuizAttempt::count());
        $this->actingAs($d['s'])->post("/belajar/quiz/{$coded->id}/mulai", ['access_code' => 'rahasia'])->assertRedirect();
        $this->assertSame(1, QuizAttempt::count());
    }

    public function test_attempt_limit_and_resume(): void
    {
        $d = $this->d();
        $q = $this->mk($d['c'], 'uraian', []);
        $quiz = $this->quiz($d['c'], [$q], ['max_attempts' => 1]);
        $this->actingAs($d['s'])->post("/belajar/quiz/{$quiz->id}/mulai");
        $this->actingAs($d['s'])->post("/belajar/quiz/{$quiz->id}/mulai"); // lanjutkan, bukan percobaan baru
        $this->assertSame(1, QuizAttempt::count());
        $at = QuizAttempt::first();
        $this->actingAs($d['s'])->post("/belajar/pengerjaan/{$at->id}/kumpul");
        $this->actingAs($d['s'])->post("/belajar/quiz/{$quiz->id}/mulai")->assertSessionHasErrors('quiz');
        $this->assertSame(1, QuizAttempt::count());
    }

    public function test_expired_attempt_is_autosubmitted_and_rejects_answers(): void
    {
        $d = $this->d();
        $q = $this->mk($d['c'], 'isian', [['body' => 'x']]);
        $quiz = $this->quiz($d['c'], [$q]);
        $this->actingAs($d['s'])->post("/belajar/quiz/{$quiz->id}/mulai");
        $at = QuizAttempt::first();
        $at->forceFill(['deadline_at' => now()->subMinute()])->save();
        $this->actingAs($d['s'])->putJson("/belajar/pengerjaan/{$at->id}/soal/{$q->id}", ['answer' => ['text' => 'x']])->assertStatus(409);
        $this->assertNotSame('in_progress', $at->fresh()->status);
        $this->assertEquals(0.0, (float) $at->fresh()->score);
    }

    public function test_cron_finalizes_expired(): void
    {
        $d = $this->d();
        $q = $this->mk($d['c'], 'pg', [['body' => 'A', 'is_correct' => true], ['body' => 'B', 'is_correct' => false]]);
        $quiz = $this->quiz($d['c'], [$q]);
        $this->actingAs($d['s'])->post("/belajar/quiz/{$quiz->id}/mulai");
        QuizAttempt::first()->forceFill(['deadline_at' => now()->subMinute()])->save();
        $this->artisan('quiz:finalize')->assertSuccessful();
        $this->assertSame('graded', QuizAttempt::first()->status);
    }

    public function test_result_visibility_and_leaderboard_toggle(): void
    {
        $d = $this->d();
        $q = $this->mk($d['c'], 'pg', [['body' => 'A', 'is_correct' => true], ['body' => 'B', 'is_correct' => false]]);
        $quiz = $this->quiz($d['c'], [$q], ['show_result' => 'never']);
        $this->actingAs($d['s'])->post("/belajar/quiz/{$quiz->id}/mulai");
        $at = QuizAttempt::first();
        $this->actingAs($d['s'])->post("/belajar/pengerjaan/{$at->id}/kumpul");
        $r = $this->actingAs($d['s'])->get("/belajar/pengerjaan/{$at->id}/hasil");
        $r->assertOk();
        $this->assertStringNotContainsString('is_correct', $r->getContent());

        $this->actingAs($d['s'])->get("/belajar/quiz/{$quiz->id}")->assertOk()->assertSee('leaderboard');
        $d['c']->update(['leaderboard_enabled' => false]);
        $page = $this->actingAs($d['s'])->get("/belajar/quiz/{$quiz->id}")->getContent();
        $this->assertStringContainsString('"leaderboard":null', $page);
    }

    public function test_question_validation_and_locking(): void
    {
        $d = $this->d();
        $base = "/ruang-guru/mapel/{$d['c']->id}/soal";
        $this->actingAs($d['guru'])->post($base, ['type' => 'pg', 'body' => 'x', 'points' => 1, 'options' => [['body' => 'a', 'is_correct' => true], ['body' => 'b', 'is_correct' => true]]])
            ->assertSessionHasErrors('options');
        $this->actingAs($d['guru'])->post($base, ['type' => 'pg', 'body' => 'x', 'points' => 1, 'options' => [['body' => 'a', 'is_correct' => true], ['body' => 'b']]])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, Question::count());

        $q = Question::first();
        $quiz = $this->quiz($d['c'], [$q]);
        $this->actingAs($d['guru'])->delete("/ruang-guru/soal/{$q->id}")->assertSessionHasErrors('delete');
        $this->actingAs($d['s'])->post("/belajar/quiz/{$quiz->id}/mulai");
        $this->actingAs($d['guru'])->put("/ruang-guru/soal/{$q->id}", ['type' => 'pg', 'body' => 'y', 'points' => 1, 'options' => [['body' => 'a', 'is_correct' => true], ['body' => 'b']]])
            ->assertStatus(422);
        $this->actingAs($d['guru'])->post("/ruang-guru/quiz/{$quiz->id}/soal", ['items' => []])->assertSessionHasErrors('questions');
        $this->actingAs($d['guru'])->delete("/ruang-guru/quiz/{$quiz->id}")->assertSessionHasErrors('delete');
    }

    public function test_quiz_crud_and_foreign_questions_ignored(): void
    {
        $d = $this->d();
        $lain = Course::create(['teacher_id' => $d['guru2']->id, 'academic_year_id' => $d['c']->academic_year_id, 'title' => 'Lain']);
        $asing = $this->mk($lain, 'uraian', []);
        $this->actingAs($d['guru'])->post("/ruang-guru/mapel/{$d['c']->id}/quiz", [
            'title' => 'UTS', 'opens_at' => '2026-11-01T08:00', 'closes_at' => '2026-11-01T10:00', 'duration_min' => 60,
            'max_attempts' => 1, 'show_result' => 'after_submit', 'access_code' => 'abcd',
        ])->assertSessionHasNoErrors();
        $quiz = Quiz::first();
        $this->assertSame('2026-11-01 01:00:00', $quiz->opens_at->format('Y-m-d H:i:s')); // WIB -> UTC
        $this->assertNotSame('abcd', $quiz->access_code_hash);
        $this->actingAs($d['guru'])->post("/ruang-guru/quiz/{$quiz->id}/soal", ['items' => [['id' => $asing->id, 'points' => 1]]]);
        $this->assertSame(0, $quiz->questions()->count());
        $this->actingAs($d['guru'])->post("/ruang-guru/mapel/{$d['c']->id}/quiz", [
            'title' => 'X', 'opens_at' => '2026-11-01T10:00', 'closes_at' => '2026-11-01T08:00', 'max_attempts' => 1, 'show_result' => 'never',
        ])->assertSessionHasErrors('closes_at');
        $page = $this->actingAs($d['guru'])->get("/ruang-guru/quiz/{$quiz->id}")->getContent();
        $this->assertStringNotContainsString('access_code_hash', $page);
    }

    public function test_importer_parses_all_types(): void
    {
        $lines = [
            '1. Ibu kota Indonesia?', 'A. Bandung', 'B. Jakarta', 'Jawaban: B', 'Skor: 2', 'Pembahasan: Jakarta.',
            '2. Pilih bilangan genap', 'A. 1', 'B. 2', 'C. 4', 'Jawaban: B,C',
            '3. Bumi itu bulat', 'Jawaban: Benar',
            '4. Alat pemutar baut adalah ...', 'Jawaban: obeng | screwdriver',
            '5. Jodohkan', 'Busi => Api', 'Oli => Pelumas',
            '6. Jelaskan cara kerja motor 4 tak', 'Jenis: uraian',
        ];
        $res = QuestionImporter::parse($lines);
        $this->assertSame([], $res['errors']);
        $this->assertSame(['pg', 'pg_kompleks', 'benar_salah', 'isian', 'menjodohkan', 'uraian'], array_column($res['items'], 'type'));
        $this->assertEquals(2, $res['items'][0]['points']);
        foreach ($res['items'] as $it) {
            \App\Http\Controllers\Guru\QuestionController::normalize($it + ['options' => []]); // lolos validasi
        }
    }

    public function test_import_upload_preview_and_commit(): void
    {
        $d = $this->d();
        $f = \Illuminate\Http\UploadedFile::fake()->createWithContent('soal.txt', "1. Dua tambah dua?\nA. 3\nB. 4\nJawaban: B\n");
        $base = "/ruang-guru/mapel/{$d['c']->id}/soal/impor";
        $this->actingAs($d['guru'])->post($base, ['file' => $f])->assertSessionHasNoErrors();
        $this->assertSame(0, Question::count());
        $this->actingAs($d['guru2'])->post($base.'/simpan')->assertForbidden();
        $this->actingAs($d['guru'])->post($base.'/simpan')->assertSessionHasNoErrors();
        $this->assertSame(1, Question::count());
    }

    public function test_matching_ids_do_not_leak_and_key_hidden_while_attempts_remain(): void
    {
        $d = $this->d();
        $m = $this->mk($d['c'], 'menjodohkan', [['body' => 'Busi', 'match_body' => 'Api'], ['body' => 'Oli', 'match_body' => 'Pelumas']]);
        $quiz = $this->quiz($d['c'], [$m], ['max_attempts' => 2, 'show_result' => 'after_submit']);
        $this->actingAs($d['s'])->post("/belajar/quiz/{$quiz->id}/mulai");
        $at = QuizAttempt::first();
        $r = $this->actingAs($d['s'])->get("/belajar/pengerjaan/{$at->id}")->assertOk();
        $props = $r->viewData('page')['props'];
        $it = $props['items'][0] ?? null;
        $leftIds = array_column($it['lefts'], 'id');
        $this->assertEmpty(array_intersect($leftIds, array_column($it['rights'], 'id')));
        $this->actingAs($d['s'])->post("/belajar/pengerjaan/{$at->id}/kumpul");
        $this->actingAs($d['s'])->get("/belajar/pengerjaan/{$at->id}/hasil")->assertOk();
        $res = $this->actingAs($d['s'])->get("/belajar/pengerjaan/{$at->id}/hasil")->getContent();
        $this->assertStringContainsString('"can_see":false', $res); // masih ada sisa percobaan
    }
}

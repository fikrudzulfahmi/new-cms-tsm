<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizGrader;
use App\Support\Wib;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class QuizController extends Controller
{
    private function own(Request $r, Course $c): void
    {
        abort_unless($c->teacher_id === $r->user()->id, 403);
    }

    private function ownQuiz(Request $r, Quiz $q): void
    {
        $this->own($r, $q->course);
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'opens_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'closes_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'duration_min' => ['nullable', 'integer', 'min:1', 'max:600'],
            'shuffle_questions' => ['boolean'],
            'shuffle_options' => ['boolean'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'pass_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'access_code' => ['nullable', 'string', 'min:4', 'max:40'],
            'clear_code' => ['boolean'],
            'show_result' => ['required', 'in:after_submit,after_close,never'],
            'is_published' => ['boolean'],
        ];
    }

    private function fields(Request $request, ?Quiz $quiz = null): array
    {
        $d = $request->validate($this->rules());
        $o = Wib::parse($d['opens_at']);
        $c = Wib::parse($d['closes_at']);
        if ($c->lte($o)) {
            throw ValidationException::withMessages(['closes_at' => 'Waktu tutup harus setelah waktu buka.']);
        }
        $out = [
            'title' => trim($d['title']), 'description' => $d['description'] ?? null, 'opens_at' => $o, 'closes_at' => $c,
            'duration_min' => $d['duration_min'] ?? null, 'shuffle_questions' => (bool) ($d['shuffle_questions'] ?? false),
            'shuffle_options' => (bool) ($d['shuffle_options'] ?? false), 'max_attempts' => $d['max_attempts'],
            'pass_score' => $d['pass_score'] ?? null, 'show_result' => $d['show_result'], 'is_published' => (bool) ($d['is_published'] ?? false),
        ];
        if (! empty($d['access_code'])) {
            $out['access_code_hash'] = Hash::make($d['access_code']);
        } elseif ($quiz && ! empty($d['clear_code'])) {
            $out['access_code_hash'] = null;
        }

        return $out;
    }

    public function index(Request $request, Course $course)
    {
        $this->own($request, $course);

        return Inertia::render('Lms/Guru/Quizzes', [
            'course' => ['id' => $course->id, 'title' => $course->title],
            'quizzes' => Quiz::where('course_id', $course->id)->withCount(['attempts', 'questions'])->orderByDesc('opens_at')->get()
                ->map(fn ($q) => [
                    'id' => $q->id, 'title' => $q->title, 'opens_at' => Wib::format($q->opens_at), 'closes_at' => Wib::format($q->closes_at),
                    'is_published' => $q->is_published, 'attempts_count' => $q->attempts_count, 'questions_count' => $q->questions_count,
                ]),
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $this->own($request, $course);
        $quiz = Quiz::create(['course_id' => $course->id] + $this->fields($request));

        return redirect()->route('guru.quiz.show', $quiz)->with('success', 'Quiz dibuat. Tambahkan soal dari bank soal.');
    }

    public function show(Request $request, Quiz $quiz)
    {
        $this->ownQuiz($request, $quiz);
        $course = $quiz->course;
        $attached = $quiz->questions()->with('options')->get();
        $bank = Question::where('course_id', $course->id)->whereNotIn('id', $attached->pluck('id'))->orderByDesc('id')->get(['id', 'type', 'body', 'points']);

        $attempts = QuizAttempt::where('quiz_id', $quiz->id)->with('student:id,name')->orderByDesc('id')->get();
        $submitted = $attempts->where('status', '!=', 'in_progress');
        // Analisis butir: persentase benar per soal pada percobaan yang sudah dikumpulkan.
        $stats = DB::table('lms_quiz_answers as a')->join('lms_quiz_attempts as t', 't.id', '=', 'a.attempt_id')
            ->where('t.quiz_id', $quiz->id)->where('t.status', '!=', 'in_progress')
            ->groupBy('a.question_id')
            ->selectRaw('a.question_id, count(*) as n, sum(case when a.is_correct = 1 then 1 else 0 end) as ok')->get()->keyBy('question_id');

        return Inertia::render('Lms/Guru/QuizShow', [
            'course' => ['id' => $course->id, 'title' => $course->title],
            'quiz' => [
                'id' => $quiz->id, 'title' => $quiz->title, 'description' => $quiz->description,
                'opens_at' => Wib::format($quiz->opens_at), 'closes_at' => Wib::format($quiz->closes_at),
                'duration_min' => $quiz->duration_min, 'shuffle_questions' => $quiz->shuffle_questions, 'shuffle_options' => $quiz->shuffle_options,
                'max_attempts' => $quiz->max_attempts, 'pass_score' => $quiz->pass_score, 'show_result' => $quiz->show_result,
                'is_published' => $quiz->is_published, 'has_code' => $quiz->access_code_hash !== null, 'locked' => $attempts->isNotEmpty(),
            ],
            'attached' => $attached->map(fn ($q) => [
                'id' => $q->id, 'type' => $q->type, 'body' => $q->body, 'points' => (float) $q->pivot->points,
                'answered' => (int) ($stats[$q->id]->n ?? 0),
                'percent_correct' => ($stats[$q->id]->n ?? 0) > 0 ? round($stats[$q->id]->ok / $stats[$q->id]->n * 100) : null,
            ]),
            'bank' => $bank,
            'types' => Question::TYPES,
            'attempts' => $attempts->map(fn ($a) => [
                'id' => $a->id, 'student' => $a->student?->name, 'no' => $a->attempt_no, 'status' => $a->status,
                'score' => $a->score, 'tab_switch_count' => $a->tab_switch_count, 'submitted_at' => Wib::format($a->submitted_at),
            ]),
            'average' => $submitted->count() ? round($submitted->avg('score'), 1) : null,
        ]);
    }

    public function update(Request $request, Quiz $quiz)
    {
        $this->ownQuiz($request, $quiz);
        $f = $this->fields($request, $quiz);
        if ($quiz->attempts()->exists()) {
            // Setelah ada pengerjaan, aturan penilaian dan susunan tidak diubah agar adil.
            unset($f['shuffle_questions'], $f['shuffle_options'], $f['max_attempts'], $f['duration_min']);
        }
        $quiz->update($f);

        return back()->with('success', 'Quiz diperbarui.');
    }

    public function destroy(Request $request, Quiz $quiz)
    {
        $this->ownQuiz($request, $quiz);
        if ($quiz->attempts()->exists()) {
            return back()->withErrors(['delete' => 'Quiz sudah dikerjakan siswa; sembunyikan saja (batalkan tayang).']);
        }
        $c = $quiz->course_id;
        $quiz->delete();

        return redirect()->route('guru.quizzes', $c)->with('success', 'Quiz dihapus.');
    }

    public function setQuestions(Request $request, Quiz $quiz)
    {
        $this->ownQuiz($request, $quiz);
        if ($quiz->attempts()->exists()) {
            return back()->withErrors(['questions' => 'Quiz sudah dikerjakan; soal tidak dapat diubah.']);
        }
        $d = $request->validate([
            'items' => ['array', 'max:200'],
            'items.*.id' => ['required', 'integer'],
            'items.*.points' => ['required', 'numeric', 'min:0.5', 'max:100'],
        ]);
        $items = collect($d['items'] ?? [])->unique('id')->values();
        $valid = Question::where('course_id', $quiz->course_id)->whereIn('id', $items->pluck('id'))->pluck('id')->all();
        DB::transaction(function () use ($quiz, $items, $valid) {
            DB::table('lms_quiz_questions')->where('quiz_id', $quiz->id)->delete();
            foreach ($items->filter(fn ($i) => in_array((int) $i['id'], $valid, true))->values() as $i => $it) {
                DB::table('lms_quiz_questions')->insert(['quiz_id' => $quiz->id, 'question_id' => $it['id'], 'sort_order' => $i, 'points' => $it['points']]);
            }
        });

        return back()->with('success', 'Soal quiz disimpan.');
    }

    public function attempt(Request $request, QuizAttempt $attempt)
    {
        $quiz = $attempt->quiz;
        $this->ownQuiz($request, $quiz);
        $answers = $attempt->answers()->get()->keyBy('question_id');
        $qs = $quiz->questions()->with('options')->get();

        return Inertia::render('Lms/Guru/QuizAttempt', [
            'quiz' => ['id' => $quiz->id, 'title' => $quiz->title],
            'attempt' => ['id' => $attempt->id, 'student' => $attempt->student?->name, 'status' => $attempt->status, 'score' => $attempt->score, 'tab_switch_count' => $attempt->tab_switch_count],
            'items' => $qs->map(function ($q) use ($answers) {
                $a = $answers[$q->id] ?? null;

                return [
                    'id' => $q->id, 'type' => $q->type, 'body' => $q->body, 'max' => (float) $q->pivot->points,
                    'options' => $q->options->map->only('id', 'body', 'match_body', 'is_correct')->values(),
                    'answer' => $a?->answer, 'is_correct' => $a?->is_correct, 'awarded' => $a?->points_awarded, 'feedback' => $a?->feedback,
                ];
            }),
        ]);
    }

    public function grade(Request $request, QuizAttempt $attempt, Question $question)
    {
        $this->ownQuiz($request, $attempt->quiz);
        abort_if($attempt->status === 'in_progress', 422, 'Percobaan belum dikumpulkan.');
        $max = (float) DB::table('lms_quiz_questions')->where('quiz_id', $attempt->quiz_id)->where('question_id', $question->id)->value('points');
        abort_if($max <= 0, 404);
        $d = $request->validate(['points' => ['required', 'numeric', 'min:0', 'max:'.$max], 'feedback' => ['nullable', 'string', 'max:500']]);
        DB::transaction(function () use ($attempt, $question, $d, $request) {
            $a = $attempt->answers()->firstOrNew(['question_id' => $question->id]);
            $a->forceFill(['points_awarded' => $d['points'], 'feedback' => $d['feedback'] ?? null, 'graded_by' => $request->user()->id, 'is_correct' => (float) $d['points'] > 0])->save();
            QuizGrader::recompute($attempt);
        });

        return back()->with('success', 'Nilai disimpan.');
    }
}

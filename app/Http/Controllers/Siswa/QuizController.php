<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Services\QuizGrader;
use App\Support\Wib;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class QuizController extends Controller
{
    private function visible(Request $r, Quiz $quiz): void
    {
        abort_unless($quiz->is_published && $quiz->course->is_active && $quiz->course->hasStudent($r->user()), 404);
    }

    private function mine(Request $r, QuizAttempt $a): void
    {
        abort_unless($a->student_id === $r->user()->id, 404);
    }

    /** Jika waktu habis, kumpulkan otomatis. */
    private function expire(QuizAttempt $a): QuizAttempt
    {
        if ($a->status === 'in_progress' && now()->gte($a->deadline_at)) {
            QuizGrader::finalize($a);
            $a->refresh();
        }

        return $a;
    }

    private function canSeeResult(Quiz $quiz, QuizAttempt $a): bool
    {
        if ($a->status === 'in_progress') {
            return false;
        }
        $closed = now()->gte($quiz->closes_at);
        $used = QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', $a->student_id)->count();
        // Kunci tidak dibuka selama siswa masih punya sisa percobaan dan quiz belum ditutup.
        $final = $closed || $used >= $quiz->max_attempts;

        return match ($quiz->show_result) {
            'after_submit' => $final, 'after_close' => $closed, default => false,
        };
    }

    public function show(Request $request, Quiz $quiz)
    {
        $this->visible($request, $quiz);
        $attempts = QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', $request->user()->id)->orderBy('attempt_no')->get()
            ->map(fn ($a) => $this->expire($a));
        $course = $quiz->course;

        $board = null;
        if ($course->leaderboard_enabled && $quiz->show_result !== 'never' && ($quiz->show_result === 'after_submit' || now()->gte($quiz->closes_at))) {
            $board = DB::table('lms_quiz_attempts as a')->join('users as u', 'u.id', '=', 'a.student_id')
                ->where('a.quiz_id', $quiz->id)->where('a.status', '!=', 'in_progress')
                ->groupBy('u.id', 'u.name')->selectRaw('u.name, max(a.score) as best')->orderByDesc('best')->limit(10)->get();
        }

        return Inertia::render('Lms/Siswa/Quiz', [
            'course' => ['id' => $course->id, 'title' => $course->title],
            'quiz' => [
                'id' => $quiz->id, 'title' => $quiz->title, 'description' => $quiz->description,
                'opens_at' => Wib::format($quiz->opens_at), 'closes_at' => Wib::format($quiz->closes_at), 'duration_min' => $quiz->duration_min,
                'max_attempts' => $quiz->max_attempts, 'pass_score' => $quiz->pass_score, 'needs_code' => $quiz->access_code_hash !== null,
                'is_open' => $quiz->isOpen(), 'questions_count' => $quiz->questions()->count(),
            ],
            'attempts' => $attempts->map(fn ($a) => [
                'id' => $a->id, 'no' => $a->attempt_no, 'status' => $a->status,
                'score' => $this->canSeeResult($quiz, $a) ? $a->score : null, 'can_see' => $this->canSeeResult($quiz, $a),
            ]),
            'leaderboard' => $board,
        ]);
    }

    public function start(Request $request, Quiz $quiz)
    {
        $this->visible($request, $quiz);
        $user = $request->user();
        $d = $request->validate(['access_code' => ['nullable', 'string', 'max:40']]);

        $existing = QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', $user->id)->where('status', 'in_progress')->first();
        if ($existing && now()->lt($existing->deadline_at)) {
            return redirect()->route('siswa.quiz.attempt', $existing);
        }
        if (! $quiz->isOpen()) {
            throw ValidationException::withMessages(['quiz' => 'Quiz tidak sedang dibuka.']);
        }
        if ($quiz->questions()->count() === 0) {
            throw ValidationException::withMessages(['quiz' => 'Quiz belum memiliki soal.']);
        }
        if ($quiz->access_code_hash) {
            $key = 'quiz-code:'.$quiz->id.':'.$user->id;
            if (RateLimiter::tooManyAttempts($key, 5)) {
                throw ValidationException::withMessages(['access_code' => 'Terlalu banyak kode salah. Coba lagi beberapa menit lagi.']);
            }
            if (! Hash::check((string) ($d['access_code'] ?? ''), $quiz->access_code_hash)) {
                RateLimiter::hit($key, 300);
                throw ValidationException::withMessages(['access_code' => 'Kode quiz salah.']);
            }
            RateLimiter::clear($key);
        }

        $attempt = DB::transaction(function () use ($quiz, $user, $request) {
            DB::table('lms_quizzes')->where('id', $quiz->id)->lockForUpdate()->first();
            QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', $user->id)->where('status', 'in_progress')->get()->each(fn ($a) => QuizGrader::finalize($a));
            $n = QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', $user->id)->count();
            if ($n >= $quiz->max_attempts) {
                throw ValidationException::withMessages(['quiz' => 'Kesempatan mengerjakan sudah habis.']);
            }
            $qs = $quiz->questions()->with('options:id,question_id')->get();
            $order = $qs->pluck('id')->all();
            if ($quiz->shuffle_questions) {
                shuffle($order);
            }
            $opt = [];
            foreach ($qs as $q) {
                $ids = $q->options->pluck('id')->all();
                if ($quiz->shuffle_options || $q->type === 'menjodohkan') {
                    shuffle($ids);
                }
                $opt[$q->id] = $ids;
            }
            $now = now();
            $deadline = $quiz->duration_min ? $now->copy()->addMinutes($quiz->duration_min)->min($quiz->closes_at) : $quiz->closes_at->copy();

            return QuizAttempt::create([
                'quiz_id' => $quiz->id, 'student_id' => $user->id, 'attempt_no' => $n + 1, 'started_at' => $now, 'deadline_at' => $deadline,
                'status' => 'in_progress', 'ip' => $request->ip(), 'question_order' => $order, 'option_orders' => $opt,
            ]);
        });

        return redirect()->route('siswa.quiz.attempt', $attempt);
    }

    public function attempt(Request $request, QuizAttempt $attempt)
    {
        $this->mine($request, $attempt);
        $quiz = $attempt->quiz;
        $this->visible($request, $quiz);
        $attempt = $this->expire($attempt);
        if ($attempt->status !== 'in_progress') {
            return redirect()->route('siswa.quiz.result', $attempt);
        }

        $qs = $quiz->questions()->with('options')->get()->keyBy('id');
        $saved = $attempt->answers()->pluck('answer', 'question_id');
        $items = [];
        foreach ($attempt->question_order as $qid) {
            $q = $qs[$qid] ?? null;
            if (! $q) {
                continue;
            }
            $byId = $q->options->keyBy('id');
            $ordered = collect($attempt->option_orders[$qid] ?? [])->map(fn ($id) => $byId[$id] ?? null)->filter();
            $item = ['id' => $q->id, 'type' => $q->type, 'body' => $q->body, 'points' => (float) $q->pivot->points, 'answer' => $saved[$qid] ?? null];
            if ($q->type === 'menjodohkan') {
                $item['lefts'] = $q->options->map(fn ($o) => ['id' => $o->id, 'body' => $o->body])->values();
                $item['rights'] = $ordered->map(fn ($o) => ['id' => self::rightToken($attempt->id, $o->id), 'body' => $o->match_body])->values();
                if (is_array($item['answer']) && isset($item['answer']['pairs'])) {
                    $item['answer'] = ['pairs' => collect($item['answer']['pairs'])->map(fn ($r) => self::rightToken($attempt->id, (int) $r))->all()];
                }
            } elseif (in_array($q->type, ['pg', 'benar_salah', 'pg_kompleks'], true)) {
                $item['options'] = $ordered->map(fn ($o) => ['id' => $o->id, 'body' => $o->body])->values();
            }
            $items[] = $item;
        }

        return Inertia::render('Lms/Siswa/QuizAttempt', [
            'quiz' => ['id' => $quiz->id, 'title' => $quiz->title],
            'attempt' => ['id' => $attempt->id, 'seconds_left' => max(0, (int) now()->diffInSeconds($attempt->deadline_at, false))],
            'items' => $items,
        ]);
    }

    /** Token sisi kanan menjodohkan: tidak sama dengan id opsi kiri sehingga tidak membocorkan kunci. */
    public static function rightToken(int $attemptId, int $optionId): string
    {
        return substr(hash_hmac('sha256', $attemptId.'|'.$optionId, (string) config('app.key')), 0, 12);
    }

    private function cleanAnswer(string $type, mixed $raw, $question, int $attemptId = 0): ?array
    {
        $ids = $question->options->pluck('id')->all();
        $raw = is_array($raw) ? $raw : [];
        switch ($type) {
            case 'pg':
            case 'benar_salah':
                $o = (int) ($raw['option'] ?? 0);

                return in_array($o, $ids, true) ? ['option' => $o] : null;
            case 'pg_kompleks':
                $sel = collect((array) ($raw['options'] ?? []))->map(fn ($v) => (int) $v)->unique()->filter(fn ($v) => in_array($v, $ids, true))->values()->all();

                return ['options' => $sel];
            case 'isian':
                return ['text' => mb_substr(trim((string) ($raw['text'] ?? '')), 0, 500)];
            case 'uraian':
                return ['text' => mb_substr((string) ($raw['text'] ?? ''), 0, 5000)];
            case 'menjodohkan':
                $pairs = [];
                foreach ((array) ($raw['pairs'] ?? []) as $l => $r) {
                    $rid = collect($ids)->first(fn ($i) => hash_equals(self::rightToken($attemptId, $i), (string) $r));
                    if ($rid !== null && in_array((int) $l, $ids, true)) {
                        $pairs[(int) $l] = (int) $rid;
                    }
                }

                return ['pairs' => $pairs];
        }

        return null;
    }

    public function save(Request $request, QuizAttempt $attempt, int $question)
    {
        $this->mine($request, $attempt);
        $this->visible($request, $attempt->quiz);
        $attempt = $this->expire($attempt);
        if ($attempt->status !== 'in_progress') {
            return response()->json(['ok' => false, 'finished' => true], 409);
        }
        abort_unless(in_array($question, $attempt->question_order, true), 404);
        $q = $attempt->quiz->questions()->with('options')->where('lms_questions.id', $question)->firstOrFail();
        $clean = $this->cleanAnswer($q->type, $request->input('answer'), $q, $attempt->id);
        DB::transaction(function () use ($attempt, $question, $clean) {
            $cur = QuizAttempt::whereKey($attempt->id)->lockForUpdate()->first();
            abort_if(! $cur || $cur->status !== 'in_progress', 409);
            $row = QuizAnswer::where('attempt_id', $attempt->id)->where('question_id', $question)->lockForUpdate()->first()
                ?? new QuizAnswer(['attempt_id' => $attempt->id, 'question_id' => $question]);
            $row->answer = $clean;
            $row->save();
        });

        return response()->json(['ok' => true]);
    }

    public function tab(Request $request, QuizAttempt $attempt)
    {
        $this->mine($request, $attempt);
        $this->visible($request, $attempt->quiz);
        if ($attempt->status === 'in_progress' && now()->lt($attempt->deadline_at)) {
            $attempt->increment('tab_switch_count');
        }

        return response()->json(['ok' => true]);
    }

    public function submit(Request $request, QuizAttempt $attempt)
    {
        $this->mine($request, $attempt);
        $this->visible($request, $attempt->quiz);
        QuizGrader::finalize($attempt);

        return redirect()->route('siswa.quiz.result', $attempt);
    }

    public function result(Request $request, QuizAttempt $attempt)
    {
        $this->mine($request, $attempt);
        $quiz = $attempt->quiz;
        $this->visible($request, $quiz);
        $attempt = $this->expire($attempt);
        if ($attempt->status === 'in_progress') {
            return redirect()->route('siswa.quiz.attempt', $attempt);
        }
        $see = $this->canSeeResult($quiz, $attempt);
        $items = [];
        if ($see) {
            $answers = $attempt->answers()->get()->keyBy('question_id');
            foreach ($quiz->questions()->with('options')->get() as $q) {
                $a = $answers[$q->id] ?? null;
                $items[] = [
                    'id' => $q->id, 'type' => $q->type, 'body' => $q->body, 'explanation' => $q->explanation, 'max' => (float) $q->pivot->points,
                    'options' => $q->options->map->only('id', 'body', 'match_body', 'is_correct')->values(),
                    'answer' => $a?->answer, 'is_correct' => $a?->is_correct, 'awarded' => $a?->points_awarded, 'feedback' => $a?->feedback,
                ];
            }
        }

        return Inertia::render('Lms/Siswa/QuizResult', [
            'quiz' => ['id' => $quiz->id, 'title' => $quiz->title, 'pass_score' => $quiz->pass_score],
            'attempt' => ['id' => $attempt->id, 'status' => $attempt->status, 'score' => $see ? $attempt->score : null],
            'can_see' => $see,
            'items' => $items,
        ]);
    }
}

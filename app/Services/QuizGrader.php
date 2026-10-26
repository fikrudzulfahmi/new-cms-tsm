<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\DB;

class QuizGrader
{
    private static function norm(string $s): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $s)));
    }

    /** Bagian benar 0..1; null = perlu dinilai manual (uraian). */
    public static function fraction(Question $q, ?array $answer): ?float
    {
        if ($q->type === 'uraian') {
            return null;
        }
        $answer ??= [];
        $opts = $q->options;
        switch ($q->type) {
            case 'pg':
            case 'benar_salah':
                $o = $opts->firstWhere('id', (int) ($answer['option'] ?? 0));

                return $o && $o->is_correct ? 1.0 : 0.0;
            case 'pg_kompleks':
                $correct = $opts->where('is_correct', true)->pluck('id')->all();
                if (! $correct) {
                    return 0.0;
                }
                $picked = array_values(array_intersect(array_unique(array_map('intval', (array) ($answer['options'] ?? []))), $opts->pluck('id')->all()));
                $right = count(array_intersect($picked, $correct));
                $wrong = count($picked) - $right;

                return max(0.0, ($right - $wrong) / count($correct));
            case 'isian':
                $t = self::norm((string) ($answer['text'] ?? ''));
                if ($t === '') {
                    return 0.0;
                }
                foreach ($opts->where('is_correct', true) as $o) {
                    if (self::norm($o->body) === $t) {
                        return 1.0;
                    }
                }

                return 0.0;
            case 'menjodohkan':
                if ($opts->isEmpty()) {
                    return 0.0;
                }
                $pairs = (array) ($answer['pairs'] ?? []);
                $right = 0;
                foreach ($opts as $left) {
                    $chosen = $opts->firstWhere('id', (int) ($pairs[$left->id] ?? 0));
                    if ($chosen && $chosen->match_body === $left->match_body) {
                        $right++;
                    }
                }

                return $right / $opts->count();
        }

        return 0.0;
    }

    /** Akhiri percobaan: nilai otomatis semua soal objektif. Aman dipanggil berulang. */
    public static function finalize(QuizAttempt $attempt): void
    {
        DB::transaction(function () use ($attempt) {
            $a = QuizAttempt::whereKey($attempt->id)->lockForUpdate()->first();
            if (! $a || $a->status !== 'in_progress') {
                return;
            }
            $quiz = $a->quiz;
            $questions = $quiz->questions()->with('options')->get();
            $answers = $a->answers()->get()->keyBy('question_id');
            foreach ($questions as $q) {
                $row = $answers[$q->id] ?? new QuizAnswer(['attempt_id' => $a->id, 'question_id' => $q->id]);
                $frac = self::fraction($q, $row->answer);
                $max = (float) $q->pivot->points;
                if ($frac === null) {
                    $text = trim((string) ($row->answer['text'] ?? ''));
                    $row->is_correct = $text === '' ? false : null;
                    $row->points_awarded = $text === '' ? 0 : null;
                } else {
                    $row->is_correct = $frac >= 1.0;
                    $row->points_awarded = round($frac * $max, 2);
                }
                $row->save();
            }
            $a->forceFill(['submitted_at' => now()->min($a->deadline_at), 'status' => 'submitted'])->save();
            self::recompute($a);
        });
    }

    /** Hitung ulang skor (0-100) dan status; dipanggil setelah finalisasi/penilaian manual. */
    public static function recompute(QuizAttempt $a): void
    {
        $total = (float) DB::table('lms_quiz_questions')->where('quiz_id', $a->quiz_id)->sum('points');
        $earned = (float) $a->answers()->sum('points_awarded');
        $pending = $a->answers()->whereNull('points_awarded')->exists();
        $a->forceFill([
            'score' => $total > 0 ? round(min(100, $earned / $total * 100), 2) : 0,
            'status' => $pending ? 'submitted' : 'graded',
        ])->save();
    }
}

<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Question;
use App\Services\QuestionImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class QuestionController extends Controller
{
    private function own(Request $r, Course $c): void
    {
        abort_unless($c->teacher_id === $r->user()->id, 403);
    }

    private function ownQ(Request $r, Question $q): void
    {
        $this->own($r, $q->course);
    }

    public function index(Request $request, Course $course)
    {
        $this->own($request, $course);

        return Inertia::render('Lms/Guru/Questions', [
            'course' => ['id' => $course->id, 'title' => $course->title],
            'types' => Question::TYPES,
            'importResult' => session('import'),
            'questions' => $course->hasMany(Question::class, 'course_id')->with('options')->orderByDesc('id')->get()
                ->map(fn ($q) => [
                    'id' => $q->id, 'type' => $q->type, 'body' => $q->body, 'explanation' => $q->explanation,
                    'points' => (float) $q->points,
                    'options' => $q->options->map->only('body', 'match_body', 'is_correct')->values(),
                ]),
        ]);
    }

    /** Validasi + normalisasi satu soal menjadi array siap simpan. */
    public static function normalize(array $in): array
    {
        $v = validator($in, [
            'type' => ['required', 'in:'.implode(',', array_keys(Question::TYPES))],
            'body' => ['required', 'string', 'max:5000'],
            'explanation' => ['nullable', 'string', 'max:3000'],
            'points' => ['required', 'numeric', 'min:0.5', 'max:100'],
            'options' => ['array', 'max:12'],
            'options.*.body' => ['nullable', 'string', 'max:500'],
            'options.*.match_body' => ['nullable', 'string', 'max:500'],
            'options.*.is_correct' => ['nullable', 'boolean'],
        ])->validate();
        $type = $v['type'];
        $opts = collect($v['options'] ?? [])->map(fn ($o) => [
            'body' => trim((string) ($o['body'] ?? '')), 'match_body' => isset($o['match_body']) ? trim((string) $o['match_body']) : null,
            'is_correct' => (bool) ($o['is_correct'] ?? false),
        ])->filter(fn ($o) => $o['body'] !== '')->values();
        $fail = fn ($m) => throw ValidationException::withMessages(['options' => $m]);
        switch ($type) {
            case 'benar_salah':
                $t = $opts->firstWhere('is_correct', true);
                if (! $t || ! in_array($t['body'], ['Benar', 'Salah'], true)) {
                    $fail('Pilih jawaban Benar atau Salah.');
                }
                $opts = collect([['body' => 'Benar', 'match_body' => null, 'is_correct' => $t['body'] === 'Benar'], ['body' => 'Salah', 'match_body' => null, 'is_correct' => $t['body'] === 'Salah']]);
                break;
            case 'pg':
                $opts->count() < 2 && $fail('Minimal 2 pilihan.');
                $opts->where('is_correct', true)->count() !== 1 && $fail('Pilih tepat 1 jawaban benar.');
                break;
            case 'pg_kompleks':
                $opts->count() < 3 && $fail('Minimal 3 pilihan.');
                $opts->where('is_correct', true)->count() < 1 && $fail('Pilih minimal 1 jawaban benar.');
                break;
            case 'menjodohkan':
                $opts = $opts->filter(fn ($o) => ($o['match_body'] ?? '') !== '')->values();
                $opts->count() < 2 && $fail('Minimal 2 pasangan lengkap.');
                $opts = $opts->map(fn ($o) => $o['is_correct'] ? ['is_correct' => false] + $o : $o)->values();
                break;
            case 'isian':
                $opts = $opts->map(fn ($o) => ['is_correct' => true] + $o)->values();
                $opts->isEmpty() && $fail('Isi minimal 1 jawaban yang diterima.');
                break;
            case 'uraian':
                $opts = collect();
                break;
        }

        return [
            'q' => ['type' => $type, 'body' => trim($v['body']), 'explanation' => ($v['explanation'] ?? null) ?: null, 'points' => $v['points']],
            'opts' => $opts->all(),
        ];
    }

    public static function persist(Course $course, array $n, ?Question $q = null): Question
    {
        return DB::transaction(function () use ($course, $n, $q) {
            if ($q) {
                $q->update($n['q']);
                $q->options()->delete();
            } else {
                $q = Question::create(['course_id' => $course->id] + $n['q']);
            }
            foreach ($n['opts'] as $i => $o) {
                $q->options()->create(['body' => $o['body'], 'match_body' => $o['match_body'] ?? null, 'is_correct' => $o['is_correct'], 'sort_order' => $i]);
            }

            return $q;
        });
    }

    public function store(Request $request, Course $course)
    {
        $this->own($request, $course);
        self::persist($course, self::normalize($request->all()));

        return back()->with('success', 'Soal ditambahkan.');
    }

    public function update(Request $request, Question $question)
    {
        $this->ownQ($request, $question);
        abort_if($question->usedInAttemptedQuiz(), 422, 'Soal sudah dikerjakan siswa dan tidak dapat diubah.');
        self::persist($question->course, self::normalize($request->all()), $question);

        return back()->with('success', 'Soal diperbarui.');
    }

    public function destroy(Request $request, Question $question)
    {
        $this->ownQ($request, $question);
        if (DB::table('lms_quiz_questions')->where('question_id', $question->id)->exists()) {
            return back()->withErrors(['delete' => 'Soal masih dipakai di quiz. Lepaskan dulu dari quiz.']);
        }
        $question->delete();

        return back()->with('success', 'Soal dihapus.');
    }

    /** Pratinjau import: berkas diparse, hasil disimpan sementara di sesi (bukan disimpan ke bank). */
    public function importPreview(Request $request, Course $course)
    {
        $this->own($request, $course);
        $request->validate(['file' => ['required', 'file', 'max:2048', 'mimes:docx,rtf,txt']]);
        try {
            $lines = QuestionImporter::paragraphs($request->file('file'));
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }
        $res = QuestionImporter::parse($lines);
        $res['items'] = array_slice($res['items'], 0, 200);
        $request->session()->put('qimport.'.$course->id, $res['items']);

        return back()->with('import', ['count' => count($res['items']), 'errors' => array_slice($res['errors'], 0, 30), 'items' => $res['items']]);
    }

    public function importCommit(Request $request, Course $course)
    {
        $this->own($request, $course);
        $items = $request->session()->pull('qimport.'.$course->id, []);
        if (! $items) {
            return back()->withErrors(['file' => 'Tidak ada hasil pratinjau. Unggah berkas lagi.']);
        }
        $n = 0;
        DB::transaction(function () use ($items, $course, &$n) {
            foreach ($items as $it) {
                self::persist($course, self::normalize($it + ['options' => $it['options'] ?? []]));
                $n++;
            }
        });

        return back()->with('success', $n.' soal diimpor ke bank soal.');
    }
}

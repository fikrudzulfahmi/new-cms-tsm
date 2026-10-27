<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Services\SubmissionStorage;
use App\Support\Wib;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AssignmentController extends Controller
{
    private function own(Request $r, Course $course): void
    {
        abort_unless($course->teacher_id === $r->user()->id, 403);
    }

    private function ownAssignment(Request $r, Assignment $a): void
    {
        $this->own($r, $a->course);
    }

    public function index(Request $request, Course $course)
    {
        $this->own($request, $course);
        $assignments = Assignment::withCount('submissions')->with('criteria')
            ->where('course_id', $course->id)->orderByRaw('due_at IS NULL')->orderBy('due_at')->get();
        $graded = Submission::whereIn('assignment_id', $assignments->pluck('id'))->whereNotNull('graded_at')
            ->selectRaw('assignment_id, count(*) n')->groupBy('assignment_id')->pluck('n', 'assignment_id');

        return Inertia::render('Lms/Guru/Assignments', [
            'course' => ['id' => $course->id, 'title' => $course->title],
            'assignments' => $assignments->map(fn ($a) => [
                'id' => $a->id, 'title' => $a->title, 'instructions' => $a->instructions, 'due_at' => Wib::format($a->due_at),
                'section_id' => $a->section_id, 'attachment_name' => $a->attachment_name,
                'is_published' => $a->is_published, 'allow_late' => $a->allow_late, 'max_score' => (float) $a->max_score,
                'submissions_count' => $a->submissions_count, 'graded_count' => (int) ($graded[$a->id] ?? 0),
                'criteria' => $a->criteria->map->only('id', 'name', 'weight'),
            ]),
            'sections' => $course->sections()->get(['id', 'title']),
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $this->own($request, $course);
        $data = $this->validated($request, $course);
        $data['course_id'] = $course->id;
        $a = DB::transaction(function () use ($data, $request) {
            $a = Assignment::create($data);
            $this->storeBrief($request, $a);
            $this->syncCriteria($a, request('criteria', []));

            return $a;
        });
        ActivityLog::record('assignment.create', 'lms', 'Buat tugas: '.$a->title, $a, [], $request);

        return back()->with('success', 'Tugas dibuat.');
    }

    public function update(Request $request, Assignment $assignment)
    {
        $this->ownAssignment($request, $assignment);
        $data = $this->validated($request, $assignment->course);
        DB::transaction(function () use ($assignment, $data, $request) {
            $assignment->update($data);
            $this->storeBrief($request, $assignment);
            $this->syncCriteria($assignment, request('criteria', []));
        });
        ActivityLog::record('assignment.update', 'lms', 'Ubah tugas: '.$assignment->title, $assignment, [], $request);

        return back()->with('success', 'Tugas diperbarui.');
    }

    public function destroy(Request $request, Assignment $assignment)
    {
        $this->ownAssignment($request, $assignment);
        $paths = SubmissionFile::whereIn('submission_id', $assignment->submissions()->pluck('id'))->pluck('path')->all();
        $paths = array_merge($paths, $assignment->submissions()->whereNotNull('file_path')->pluck('file_path')->all());
        foreach (array_unique($paths) as $p) {
            SubmissionStorage::forget($p);
        }
        SubmissionStorage::forget($assignment->attachment_path);
        $title = $assignment->title;
        $assignment->delete();
        ActivityLog::record('assignment.delete', 'lms', 'Hapus tugas: '.$title, null, [], $request);

        return back()->with('success', 'Tugas dihapus.');
    }

    /** Tayangkan / sembunyikan tugas tanpa menyentuh kolom lain (dan tanpa mengganti rubrik). */
    public function togglePublished(Request $request, Assignment $assignment)
    {
        $this->ownAssignment($request, $assignment);
        $assignment->update(['is_published' => ! $assignment->is_published]);
        ActivityLog::record('assignment.publish', 'lms', ($assignment->is_published ? 'Tayangkan' : 'Sembunyikan').' tugas: '.$assignment->title, $assignment, [], $request);

        return back()->with('success', $assignment->is_published ? 'Tugas ditayangkan.' : 'Tugas disembunyikan.');
    }

    public function show(Request $request, Assignment $assignment)
    {
        $this->ownAssignment($request, $assignment);
        $students = $assignment->course->students();
        $subs = $assignment->submissions()->with('files:id,submission_id')->get()->keyBy('student_id');
        // Siswa yang sudah pindah rombel tapi pernah mengumpulkan tetap ditampilkan.
        $extra = $subs->reject(fn ($s) => $students->contains('id', $s->student_id));
        if ($extra->isNotEmpty()) {
            $students = $students->concat(User::whereIn('id', $extra->pluck('student_id'))->get(['id', 'name', 'username']));
        }

        return Inertia::render('Lms/Guru/Assignment', [
            'course' => ['id' => $assignment->course->id, 'title' => $assignment->course->title],
            'assignment' => [
                'id' => $assignment->id, 'title' => $assignment->title, 'instructions' => $assignment->instructions,
                'due_at' => Wib::format($assignment->due_at), 'allow_late' => $assignment->allow_late,
                'max_score' => (float) $assignment->max_score, 'is_published' => $assignment->is_published,
                'attachment_name' => $assignment->attachment_name,
            ],
            'criteria' => $assignment->criteria->map->only('id', 'name', 'weight'),
            'students' => $students->map(function ($s) use ($subs) {
                $sub = $subs[$s->id] ?? null;

                return [
                    'id' => $s->id, 'name' => $s->name, 'nis' => $s->username,
                    'submission' => $sub ? [
                        'id' => $sub->id, 'type' => $sub->type, 'text' => $sub->text, 'link' => $sub->link,
                        'file_name' => $sub->file_name, 'file_id' => $sub->files->first()?->id,
                        'file_size' => $sub->file_size, 'is_late' => $sub->is_late,
                        'submitted_at' => Wib::format($sub->submitted_at), 'score' => $sub->score !== null ? (float) $sub->score : null,
                        'feedback' => $sub->feedback, 'rubric_scores' => $sub->rubric_scores,
                        'graded_at' => Wib::format($sub->graded_at),
                    ] : null,
                ];
            }),
        ]);
    }

    public function grade(Request $request, Submission $submission)
    {
        $assignment = $submission->assignment;
        $this->ownAssignment($request, $assignment);
        $max = (float) $assignment->max_score;

        $data = $request->validate([
            'score' => ['nullable', 'numeric', 'min:0', 'max:'.$max],
            'feedback' => ['nullable', 'string', 'max:2000'],
            'rubric_scores' => ['nullable', 'array'],
            'rubric_scores.*' => ['nullable', 'numeric', 'min:0', 'max:'.$max],
        ]);

        $submission->update([
            'score' => $data['score'] ?? null,
            'feedback' => $data['feedback'] ?? null,
            'rubric_scores' => $data['rubric_scores'] ?? null,
            'graded_at' => now(),
            'graded_by' => $request->user()->id,
        ]);
        ActivityLog::record('assignment.grade', 'lms', 'Nilai tugas: '.$assignment->title, $submission, ['score' => $data['score'] ?? null], $request);

        return back()->with('success', 'Nilai disimpan.');
    }

    /** Hapus lampiran (nilai & feedback tetap). */
    public function deleteFile(Request $request, Submission $submission)
    {
        $this->ownAssignment($request, $submission->assignment);
        $this->purgeFiles(collect([$submission]));
        ActivityLog::record('assignment.file.delete', 'lms', 'Hapus lampiran tugas siswa', $submission, [], $request);

        return back()->with('success', 'Lampiran dihapus, nilai tetap tersimpan.');
    }

    public function deleteAllFiles(Request $request, Assignment $assignment)
    {
        $this->ownAssignment($request, $assignment);
        $subs = $assignment->submissions()->get();
        $this->purgeFiles($subs);
        ActivityLog::record('assignment.file.purge', 'lms', 'Hapus semua lampiran tugas: '.$assignment->title, $assignment, ['jumlah' => $subs->count()], $request);

        return back()->with('success', 'Semua lampiran tugas dihapus, nilai tetap tersimpan.');
    }

    public function download(Request $request, SubmissionFile $file)
    {
        $this->ownAssignment($request, $file->submission->assignment);
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->response($file->path, $file->name, [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "sandbox; default-src 'none'",
        ], 'attachment');
    }

    /** Unduh lampiran soal/instruksi milik tugas ini. */
    public function brief(Request $request, Assignment $assignment)
    {
        $this->ownAssignment($request, $assignment);
        abort_unless($assignment->attachment_path && Storage::disk('local')->exists($assignment->attachment_path), 404);

        return Storage::disk('local')->response($assignment->attachment_path, $assignment->attachment_name, [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "sandbox; default-src 'none'",
        ], 'attachment');
    }

    private function purgeFiles($submissions): void
    {
        foreach ($submissions as $sub) {
            SubmissionStorage::forget($sub->file_path);
            foreach ($sub->files as $f) {
                SubmissionStorage::forget($f->path);
            }
            $sub->files()->delete();
            $sub->update(['file_path' => null, 'file_name' => null, 'file_mime' => null, 'file_size' => null]);
        }
    }

    /** Lampiran soal/instruksi dari guru (opsional, ganti berkas lama). */
    private function storeBrief(Request $request, Assignment $a): void
    {
        if (! $request->hasFile('brief')) {
            return;
        }
        $request->validate(['brief' => ['file', 'max:5120']]);
        $f = SubmissionStorage::store($request->file('brief'), 'lms/assignments/'.$a->id);
        SubmissionStorage::forget($a->attachment_path);
        $a->update(['attachment_path' => $f['path'], 'attachment_name' => $f['name']]);
    }

    private function validated(Request $request, Course $course): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'instructions' => ['nullable', 'string', 'max:10000'],
            'due_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'allow_late' => ['boolean'],
            'max_score' => ['required', 'numeric', 'min:1', 'max:1000'],
            'is_published' => ['boolean'],
            'section_id' => ['nullable', Rule::in($course->sections()->pluck('id')->all())],
            'criteria' => ['nullable', 'array', 'max:10'],
            'criteria.*.id' => ['nullable', 'integer'],
            'criteria.*.name' => ['required', 'string', 'max:120'],
            'criteria.*.weight' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $data['due_at'] = Wib::parse($data['due_at'] ?? null);
        unset($data['criteria']);

        return $data;
    }

    /**
     * Sinkronkan kriteria rubrik tanpa mengganti ID yang sudah ada: skor rubrik tersimpan
     * sebagai JSON {criterion_id: nilai}, jadi delete+insert akan memutus semua nilai lama.
     */
    private function syncCriteria(Assignment $a, array $criteria): void
    {
        $keep = [];
        foreach (array_values($criteria) as $i => $c) {
            if (trim((string) ($c['name'] ?? '')) === '') {
                continue;
            }
            $attrs = [
                'name' => mb_substr(trim($c['name']), 0, 120),
                'weight' => max(1, (int) ($c['weight'] ?? 1)),
                'sort_order' => $i,
            ];
            $id = (int) ($c['id'] ?? 0);
            $row = $id ? $a->criteria()->whereKey($id)->first() : null;
            if ($row) {
                $row->update($attrs);
                $keep[] = $row->id;
            } else {
                $keep[] = $a->criteria()->create($attrs)->id;
            }
        }
        $a->criteria()->whereNotIn('id', $keep)->delete();
    }
}

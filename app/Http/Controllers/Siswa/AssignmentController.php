<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Lesson;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Services\SubmissionStorage;
use App\Support\Wib;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AssignmentController extends Controller
{
    private function visible(Request $r, Assignment $a): void
    {
        abort_unless(
            $a->is_published && $a->course->is_active && $a->course->hasStudent($r->user()),
            404
        );
    }

    public function show(Request $request, Assignment $assignment)
    {
        $this->visible($request, $assignment);
        $sub = Submission::where('assignment_id', $assignment->id)->where('student_id', $request->user()->id)->first();

        return Inertia::render('Lms/Siswa/Assignment', [
            'course' => ['id' => $assignment->course->id, 'title' => $assignment->course->title],
            'assignment' => [
                'id' => $assignment->id, 'title' => $assignment->title, 'instructions' => $assignment->instructions,
                'due_at' => Wib::format($assignment->due_at), 'allow_late' => $assignment->allow_late,
                'max_score' => (float) $assignment->max_score, 'attachment_name' => $assignment->attachment_name,
                'past_due' => $assignment->isPastDue(), 'accepts' => $assignment->acceptsSubmission(),
            ],
            'criteria' => $assignment->criteria->map->only('id', 'name', 'weight'),
            'submission' => $sub ? [
                'id' => $sub->id, 'type' => $sub->type, 'text' => $sub->text, 'link' => $sub->link,
                'file_name' => $sub->file_name, 'file_id' => $sub->files()->value('id'),
                'submitted_at' => Wib::format($sub->submitted_at), 'is_late' => $sub->is_late,
                'score' => $sub->isGraded() ? (float) $sub->score : null,
                'feedback' => $sub->isGraded() ? $sub->feedback : null,
                'graded' => $sub->isGraded(), 'rubric_scores' => $sub->isGraded() ? $sub->rubric_scores : null,
            ] : null,
        ]);
    }

    public function submit(Request $request, Assignment $assignment)
    {
        $this->visible($request, $assignment);
        abort_unless($assignment->acceptsSubmission(), 403, 'Batas waktu pengumpulan sudah lewat.');

        $data = $request->validate([
            'type' => ['required', 'in:text,link,file'],
            'text' => ['required_if:type,text', 'nullable', 'string', 'max:20000'],
            'link' => ['required_if:type,link', 'nullable', 'string', 'max:500', 'url:https'],
            'file' => ['required_if:type,file', 'nullable', 'file', 'max:5120'],
        ]);

        $late = $assignment->isPastDue();
        $stored = null;

        try {
            DB::transaction(function () use ($assignment, $request, $data, $late, &$stored) {
                $mine = Submission::where('assignment_id', $assignment->id)->where('student_id', $request->user()->id);
                // Klaim baris lebih dulu supaya klik ganda tidak menabrak unique(assignment_id, student_id).
                if (! (clone $mine)->exists()) {
                    DB::table('lms_submissions')->insertOrIgnore([
                        'assignment_id' => $assignment->id, 'student_id' => $request->user()->id,
                        'type' => 'text', 'is_late' => false, 'submitted_at' => now(),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                $sub = $mine->lockForUpdate()->firstOrFail();
                abort_if($sub->isGraded(), 403, 'Tugas sudah dinilai dan tidak bisa diubah.');

                if ($data['type'] === 'file') {
                    $stored = SubmissionStorage::store($request->file('file'), 'lms/submissions/'.$assignment->id);
                }

                // Pengumpulan ulang: buang berkas fisik & baris lama agar tidak menumpuk.
                foreach ($sub->files as $old) {
                    SubmissionStorage::forget($old->path);
                }
                $sub->files()->delete();
                if ($sub->file_path && $sub->file_path !== ($stored['path'] ?? null)) {
                    SubmissionStorage::forget($sub->file_path);
                }

                $sub->fill([
                    'type' => $data['type'],
                    'text' => $data['type'] === 'text' ? $data['text'] : null,
                    'link' => $data['type'] === 'link' ? $data['link'] : null,
                    'file_path' => $stored['path'] ?? null,
                    'file_name' => $stored['name'] ?? null,
                    'file_mime' => $stored['mime'] ?? null,
                    'file_size' => $stored['size'] ?? null,
                    'is_late' => $late,
                    'submitted_at' => now(),
                ]);
                $sub->save();

                if ($stored) {
                    $sub->files()->create($stored);
                }
            });
        } catch (\Throwable $e) {
            // Transaksi batal: berkas fisik yang sudah terlanjur ditulis jangan ditinggal.
            if ($stored) {
                SubmissionStorage::forget($stored['path']);
            }
            throw $e;
        }

        return redirect('/belajar/tugas/'.$assignment->id)->with('success', $late ? 'Tugas dikumpulkan (terlambat).' : 'Tugas dikumpulkan.');
    }

    public function download(Request $request, SubmissionFile $file)
    {
        $sub = $file->submission;
        $this->visible($request, $sub->assignment);
        abort_unless($sub->student_id === $request->user()->id, 404);
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->response($file->path, $file->name, [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "sandbox; default-src 'none'",
        ], 'attachment');
    }

    public function brief(Request $request, Assignment $assignment)
    {
        $this->visible($request, $assignment);
        abort_unless($assignment->attachment_path && Storage::disk('local')->exists($assignment->attachment_path), 404);

        return Storage::disk('local')->response($assignment->attachment_path, $assignment->attachment_name, [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "sandbox; default-src 'none'",
        ], 'attachment');
    }
}

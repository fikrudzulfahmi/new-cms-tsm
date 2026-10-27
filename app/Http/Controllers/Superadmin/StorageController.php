<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\SubmissionFile;
use App\Models\Setting;
use App\Services\SubmissionStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class StorageController extends Controller
{
    public function index()
    {
        $files = SubmissionFile::orderByDesc('size')->limit(100)->get();
        $subs = \App\Models\Submission::whereIn('id', $files->pluck('submission_id'))->get()->keyBy('id');
        $assignments = Assignment::whereIn('id', $subs->pluck('assignment_id'))->get()->keyBy('id');
        $courses = Course::with('year:id,name')->get()->keyBy('id');

        $perCourse = [];
        foreach ($files as $f) {
            $sub = $subs[$f->submission_id] ?? null;
            $a = $sub ? $assignments->firstWhere('id', $sub->assignment_id) : null;
            if (! $a) {
                continue;
            }
            $key = $a->course_id;
            $perCourse[$key] = ($perCourse[$key] ?? 0) + $f->size;
        }
        arsort($perCourse);

        return Inertia::render('Lms/Superadmin/Storage', [
            'total' => (int) SubmissionFile::sum('size'),
            'count' => SubmissionFile::count(),
            'retention' => (int) Setting::get('lms.assignment_file_days', 180),
            'perCourse' => collect($perCourse)->map(fn ($bytes, $id) => [
                'id' => $id, 'title' => $courses[$id]->title ?? '-', 'year' => $courses[$id]->year->name ?? null, 'bytes' => (int) $bytes,
            ])->values(),
            'topFiles' => $files->map(function ($f) use ($subs, $assignments, $courses) {
                $sub = $subs[$f->submission_id] ?? null;
                $a = $sub ? $assignments->firstWhere('id', $sub->assignment_id) : null;

                return [
                    'id' => $f->id, 'name' => $f->name, 'size' => (int) $f->size,
                    'course' => $a ? ($courses[$a->course_id]->title ?? '-') : '-',
                    'assignment' => $a->title ?? '-', 'created_at' => $f->created_at?->format('Y-m-d'),
                ];
            }),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['retention' => ['required', 'integer', 'min:0', 'max:3650']]);
        Setting::set('lms.assignment_file_days', (string) $data['retention'], 'lms', 'number');
        ActivityLog::record('storage.retention', 'lms', 'Atur retensi lampiran tugas: '.$data['retention'].' hari', null, [], $request);

        return back()->with('success', 'Pengaturan retensi disimpan. 0 = pembersihan otomatis mati.');
    }

    public function destroy(Request $request, SubmissionFile $file)
    {
        SubmissionStorage::forget($file->path);
        $sub = $file->submission;
        if ($sub && $sub->file_path === $file->path) {
            $sub->update(['file_path' => null, 'file_name' => null, 'file_mime' => null, 'file_size' => null]);
        }
        $file->delete();
        ActivityLog::record('storage.file.delete', 'lms', 'Superadmin hapus berkas tugas', null, ['file' => $file->name], $request);

        return back()->with('success', 'Berkas dihapus.');
    }
}

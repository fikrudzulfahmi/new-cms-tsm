<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LearnController extends Controller
{
    private function authorizeCourse(Request $r, Course $course): void
    {
        abort_unless($course->is_active && $course->hasStudent($r->user()), 403);
    }

    private function publishedLessonIds(Course $course)
    {
        return Lesson::whereIn('section_id', $course->sections()->where('is_published', true)->pluck('id'))
            ->where('is_published', true)->pluck('id');
    }

    public function index(Request $request)
    {
        $courses = Course::with('teacher:id,name')->whereIn('id', Course::idsForStudent($request->user()))->orderBy('title')->get();

        return Inertia::render('Lms/Siswa/Index', [
            'courses' => $courses->map(function ($c) use ($request) {
                $ids = $this->publishedLessonIds($c);
                $done = LessonProgress::where('student_id', $request->user()->id)->whereIn('lesson_id', $ids)->whereNotNull('completed_at')->count();

                return ['id' => $c->id, 'title' => $c->title, 'teacher' => $c->teacher?->name, 'total' => $ids->count(), 'done' => $done];
            }),
        ]);
    }

    public function course(Request $request, Course $course)
    {
        $this->authorizeCourse($request, $course);
        $doneIds = LessonProgress::where('student_id', $request->user()->id)->whereNotNull('completed_at')->pluck('lesson_id')->all();

        return Inertia::render('Lms/Siswa/Course', [
            'course' => ['id' => $course->id, 'title' => $course->title, 'description' => $course->description],
            'sections' => $course->sections()->where('is_published', true)
                ->with(['lessons' => fn ($q) => $q->where('is_published', true)->select('id', 'section_id', 'type', 'title', 'est_minutes', 'sort_order')])
                ->get()->map(fn ($s) => [
                    'id' => $s->id, 'title' => $s->title,
                    'lessons' => $s->lessons->map(fn ($l) => $l->only('id', 'type', 'title', 'est_minutes') + ['done' => in_array($l->id, $doneIds, true)]),
                ]),
        ]);
    }

    private function visibleLesson(Request $request, Lesson $lesson): Course
    {
        $course = $lesson->section->course;
        $this->authorizeCourse($request, $course);
        abort_unless($lesson->is_published && $lesson->section->is_published, 404);

        return $course;
    }

    public function lesson(Request $request, Lesson $lesson)
    {
        $course = $this->visibleLesson($request, $lesson);

        return Inertia::render('Lms/Siswa/Lesson', [
            'course' => ['id' => $course->id, 'title' => $course->title],
            'lesson' => $lesson->only('id', 'type', 'title', 'content', 'video_url', 'attachment_name', 'est_minutes') + ['has_file' => (bool) $lesson->attachment_path],
            'done' => LessonProgress::where('lesson_id', $lesson->id)->where('student_id', $request->user()->id)->whereNotNull('completed_at')->exists(),
        ]);
    }

    public function complete(Request $request, Lesson $lesson)
    {
        $this->visibleLesson($request, $lesson);
        LessonProgress::updateOrCreate(
            ['lesson_id' => $lesson->id, 'student_id' => $request->user()->id],
            ['completed_at' => now()]
        );

        return back()->with('success', 'Ditandai selesai.');
    }

    /** Berkas lampiran: siswa terdaftar, atau guru pemilik mapel. */
    public function file(Request $request, Lesson $lesson)
    {
        $user = $request->user();
        $course = $lesson->section->course;
        if ($user->role === 'guru') {
            abort_unless($course->teacher_id === $user->id, 403);
        } else {
            $this->visibleLesson($request, $lesson);
        }
        abort_unless($lesson->attachment_path && Storage::disk('local')->exists($lesson->attachment_path), 404);

        return Storage::disk('local')->response($lesson->attachment_path, $lesson->attachment_name, [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "sandbox; default-src 'none'",
        ], 'attachment');
    }
}

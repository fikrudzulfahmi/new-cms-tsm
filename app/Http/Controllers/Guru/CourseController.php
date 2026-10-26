<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CourseController extends Controller
{
    private function own(Request $r, Course $course): void
    {
        abort_unless($course->teacher_id === $r->user()->id, 403);
    }

    private function ownSection(Request $r, CourseSection $s): void
    {
        $this->own($r, $s->course);
    }

    private function ownLesson(Request $r, Lesson $l): void
    {
        $this->own($r, $l->section->course);
    }

    public function index(Request $request)
    {
        return Inertia::render('Lms/Guru/Index', [
            'courses' => Course::with(['classes:id,name', 'year:id,name'])
                ->where('teacher_id', $request->user()->id)->orderByDesc('academic_year_id')->orderBy('title')->get()
                ->map(fn ($c) => [
                    'id' => $c->id, 'title' => $c->title, 'year' => $c->year?->name,
                    'classes' => $c->classes->pluck('name'), 'is_active' => $c->is_active,
                ]),
        ]);
    }

    public function show(Request $request, Course $course)
    {
        $this->own($request, $course);

        return Inertia::render('Lms/Guru/Course', [
            'course' => ['id' => $course->id, 'title' => $course->title, 'classes' => $course->classes()->pluck('name')],
            'sections' => $course->sections()->with('lessons:id,section_id,type,title,content,video_url,attachment_name,est_minutes,sort_order,is_published')->get(),
        ]);
    }

    public function storeSection(Request $request, Course $course)
    {
        $this->own($request, $course);
        $d = $request->validate(['title' => ['required', 'string', 'max:150']]);
        $course->sections()->create(['title' => trim($d['title']), 'sort_order' => (int) $course->sections()->max('sort_order') + 1]);

        return back()->with('success', 'Bab ditambahkan.');
    }

    public function updateSection(Request $request, CourseSection $section)
    {
        $this->ownSection($request, $section);
        $d = $request->validate(['title' => ['required', 'string', 'max:150'], 'is_published' => ['boolean']]);
        $section->update(['title' => trim($d['title']), 'is_published' => $d['is_published'] ?? $section->is_published]);

        return back()->with('success', 'Bab diperbarui.');
    }

    public function destroySection(Request $request, CourseSection $section)
    {
        $this->ownSection($request, $section);
        foreach ($section->lessons as $l) {
            $l->attachment_path && Storage::disk('local')->delete($l->attachment_path);
        }
        $section->delete();

        return back()->with('success', 'Bab dihapus.');
    }

    public function moveSection(Request $request, CourseSection $section)
    {
        $this->ownSection($request, $section);
        $dir = $request->input('dir') === 'up' ? -1 : 1;
        $list = $section->course->sections()->get()->values();
        $i = $list->search(fn ($s) => $s->id === $section->id);
        $j = $i + $dir;
        if (isset($list[$j])) {
            foreach ($list as $k => $s) {
                $s->forceFill(['sort_order' => $k])->save();
            }
            $list[$i]->forceFill(['sort_order' => $j])->save();
            $list[$j]->forceFill(['sort_order' => $i])->save();
        }

        return back();
    }

    private function lessonRules(): array
    {
        return [
            'type' => ['required', 'in:reading,video,file'],
            'title' => ['required', 'string', 'max:150'],
            'content' => ['nullable', 'string', 'max:20000'],
            'video_url' => ['nullable', 'string', 'max:255'],
            'est_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'is_published' => ['boolean'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    private function fill(Request $request, Lesson $lesson, array $d): array
    {
        $attrs = [
            'type' => $d['type'], 'title' => trim($d['title']), 'content' => $d['content'] ?? null,
            'est_minutes' => $d['est_minutes'] ?? null, 'is_published' => $d['is_published'] ?? false,
            'video_url' => null,
        ];
        if ($d['type'] === 'video') {
            $url = Lesson::normalizeVideoUrl($d['video_url'] ?? '');
            if ($url === null) {
                throw \Illuminate\Validation\ValidationException::withMessages(['video_url' => 'Tautan video harus YouTube atau Google Drive (https).']);
            }
            $attrs['video_url'] = $url;
        }
        if ($d['type'] === 'file' && ! $request->hasFile('file') && ! $lesson->attachment_path) {
            throw \Illuminate\Validation\ValidationException::withMessages(['file' => 'Pilih berkas PDF/JPG/PNG (maks 5 MB).']);
        }
        if ($request->hasFile('file') && $d['type'] === 'file') {
            $f = $request->file('file');
            if ($lesson->attachment_path) {
                Storage::disk('local')->delete($lesson->attachment_path);
            }
            $attrs['attachment_path'] = $f->store('lms/lessons', 'local');
            $attrs['attachment_name'] = mb_substr(preg_replace('/[^\w.\- ]+/u', '_', $f->getClientOriginalName()), 0, 150);
        }
        if ($d['type'] !== 'file' && $lesson->attachment_path) {
            Storage::disk('local')->delete($lesson->attachment_path);
            $attrs['attachment_path'] = $attrs['attachment_name'] = null;
        }

        return $attrs;
    }

    public function storeLesson(Request $request, CourseSection $section)
    {
        $this->ownSection($request, $section);
        $d = $request->validate($this->lessonRules());
        if ($d['type'] === 'file' && ! $request->hasFile('file')) {
            return back()->withErrors(['file' => 'Pilih berkas PDF/JPG/PNG (maks 5 MB).']);
        }
        $lesson = new Lesson(['section_id' => $section->id, 'sort_order' => (int) $section->lessons()->max('sort_order') + 1]);
        $lesson->fill($this->fill($request, $lesson, $d))->save();

        return back()->with('success', 'Materi ditambahkan.');
    }

    public function updateLesson(Request $request, Lesson $lesson)
    {
        $this->ownLesson($request, $lesson);
        $d = $request->validate($this->lessonRules());
        $lesson->fill($this->fill($request, $lesson, $d))->save();

        return back()->with('success', 'Materi diperbarui.');
    }

    public function destroyLesson(Request $request, Lesson $lesson)
    {
        $this->ownLesson($request, $lesson);
        $lesson->attachment_path && Storage::disk('local')->delete($lesson->attachment_path);
        $lesson->delete();

        return back()->with('success', 'Materi dihapus.');
    }

    public function progress(Request $request, Course $course)
    {
        $this->own($request, $course);
        $ids = Lesson::whereIn('section_id', $course->sections()->where('is_published', true)->pluck('id'))->where('is_published', true)->pluck('id');
        $total = $ids->count();
        $done = LessonProgress::whereIn('lesson_id', $ids)->whereNotNull('completed_at')
            ->selectRaw('student_id, count(*) as n')->groupBy('student_id')->pluck('n', 'student_id');
        $students = \App\Models\User::whereIn('id', \DB::table('lms_course_classes as cc')
            ->join('core_class_members as m', fn ($j) => $j->on('m.class_id', '=', 'cc.class_id')->where('m.academic_year_id', $course->academic_year_id))
            ->where('cc.course_id', $course->id)->whereNull('m.left_at')->pluck('m.student_id'))
            ->orderBy('name')->get(['id', 'name', 'username']);

        return response()->json([
            'total' => $total,
            'students' => $students->map(fn ($s) => ['name' => $s->name, 'done' => (int) ($done[$s->id] ?? 0)]),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CourseController extends Controller
{
    public function index()
    {
        $year = AcademicYear::active();

        return Inertia::render('Lms/Superadmin/Courses', [
            'activeYear' => $year?->name,
            'courses' => Course::with(['teacher:id,name', 'classes:id,name', 'year:id,name'])
                ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
                ->orderBy('title')->get()->map(fn ($c) => [
                    'id' => $c->id, 'title' => $c->title, 'description' => $c->description,
                    'teacher_id' => $c->teacher_id, 'teacher' => $c->teacher?->name, 'is_active' => $c->is_active,
                    'class_ids' => $c->classes->pluck('id'), 'classes' => $c->classes->pluck('name'),
                ]),
            'teachers' => User::where('role', 'guru')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'allClasses' => SchoolClass::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'teacher_id' => ['required', Rule::exists('users', 'id')->where('role', 'guru')->where('is_active', true)],
            'class_ids' => ['required', 'array', 'min:1'],
            'class_ids.*' => ['integer', Rule::exists('core_classes', 'id')->where('is_active', true)],
            'is_active' => ['boolean'],
        ];
    }

    public function store(Request $request)
    {
        $year = AcademicYear::active();
        if (! $year) {
            return back()->withErrors(['title' => 'Atur tahun ajaran aktif dulu.']);
        }
        $d = $request->validate($this->rules(), ['class_ids.required' => 'Pilih minimal satu rombel.']);
        $c = Course::create([
            'teacher_id' => $d['teacher_id'], 'academic_year_id' => $year->id, 'title' => trim($d['title']),
            'description' => $d['description'] ?? null, 'is_active' => $d['is_active'] ?? true,
        ]);
        $c->classes()->sync($d['class_ids']);
        ActivityLog::record('create', 'lms', 'Mapel dibuat: '.$c->title, $c);

        return back()->with('success', 'Mapel ditambahkan.');
    }

    public function update(Request $request, Course $course)
    {
        $d = $request->validate($this->rules(), ['class_ids.required' => 'Pilih minimal satu rombel.']);
        $course->update([
            'teacher_id' => $d['teacher_id'], 'title' => trim($d['title']),
            'description' => $d['description'] ?? null, 'is_active' => $d['is_active'] ?? $course->is_active,
        ]);
        $course->classes()->sync($d['class_ids']);
        ActivityLog::record('update', 'lms', 'Mapel diubah: '.$course->title, $course);

        return back()->with('success', 'Mapel diperbarui.');
    }

    public function destroy(Course $course)
    {
        foreach ($course->sections()->with('lessons')->get() as $s) {
            foreach ($s->lessons as $l) {
                if ($l->attachment_path) {
                    \Storage::disk('local')->delete($l->attachment_path);
                }
            }
        }
        $course->delete();
        ActivityLog::record('delete', 'lms', 'Mapel dihapus: '.$course->title);

        return back()->with('success', 'Mapel dihapus.');
    }
}

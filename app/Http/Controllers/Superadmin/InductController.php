<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\ClassMember;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\IndukApi;
use App\Services\SyncInduk;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RuntimeException;

class InductController extends Controller
{
    public function students(Request $request, IndukApi $api)
    {
        $year = AcademicYear::active();
        $q = trim((string) $request->query('q', ''));
        $classId = (int) $request->query('kelas', 0);

        $classes = SchoolClass::where('is_active', true)->whereNotNull('external_id')->orderBy('name')->get()->map(fn ($c) => [
            'id' => $c->id, 'name' => $c->name, 'synced_at' => $c->synced_at,
            'members' => $year ? ClassMember::where('class_id', $c->id)->where('academic_year_id', $year->id)->whereNull('left_at')->count() : 0,
        ]);

        $students = User::where('role', 'siswa')
            ->when($q !== '', fn ($b) => $b->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('username', 'like', "%{$q}%")->orWhere('nis_full', 'like', "%{$q}%")))
            ->when($classId, fn ($b) => $b->whereIn('id', ClassMember::where('class_id', $classId)->when($year, fn ($m) => $m->where('academic_year_id', $year->id))->whereNull('left_at')->select('student_id')))
            ->with(['classMembers' => fn ($m) => $m->whereNull('left_at')->when($year, fn ($x) => $x->where('academic_year_id', $year->id))->with('schoolClass:id,name')])
            ->orderBy('name')->paginate(25)->withQueryString()
            ->through(fn ($u) => [
                'id' => $u->id, 'name' => $u->name, 'username' => $u->username, 'nis' => $u->nis_full, 'nisn' => $u->nisn,
                'gender' => $u->gender, 'can_write_posts' => $u->can_write_posts, 'is_active' => $u->is_active, 'must_change_password' => $u->must_change_password,
                'kelas' => $u->classMembers->first()?->schoolClass?->name,
            ]);

        return Inertia::render('Lms/Superadmin/Students', [
            'indukReady' => $api->configured(), 'activeYear' => $year?->name,
            'classes' => $classes, 'students' => $students, 'q' => $q, 'kelas' => $classId,
            'preview' => session('preview'),
        ]);
    }

    public function teachers(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        return Inertia::render('Lms/Superadmin/Teachers', [
            'teachers' => User::where('role', 'guru')
                ->when($q !== '', fn ($b) => $b->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('username', 'like', "%{$q}%")))
                ->orderBy('name')->paginate(25)->withQueryString()
                ->through(fn ($u) => $u->only(['id', 'name', 'username', 'is_active', 'can_write_posts', 'must_change_password'])),
            'q' => $q,
        ]);
    }

    public function pullClasses(SyncInduk $sync)
    {
        try {
            $n = $sync->pullClasses();
        } catch (RuntimeException $e) {
            return back()->withErrors(['sync' => $e->getMessage()]);
        }
        ActivityLog::record('update', 'lms', 'Tarik daftar kelas dari induk: '.$n.' kelas');

        return back()->with('success', $n.' kelas ditarik dari induk. Pilih kelas lalu klik "Tarik siswa".');
    }

    /** Pratinjau: tidak menyimpan apa pun. */
    public function preview(SchoolClass $class, SyncInduk $sync)
    {
        try {
            $r = $sync->previewClass($class);
        } catch (RuntimeException $e) {
            return back()->withErrors(['sync' => $e->getMessage()]);
        }

        return back()->with('preview', ['class_id' => $class->id, 'class' => $class->name] + $r);
    }

    public function pull(SchoolClass $class, SyncInduk $sync)
    {
        try {
            $r = $sync->pullClass($class);
        } catch (RuntimeException $e) {
            return back()->withErrors(['sync' => $e->getMessage()]);
        }
        ActivityLog::record('update', 'lms', 'Tarik siswa kelas '.$class->name, $class, collect($r)->except('rows')->all());

        return back()->with('success', sprintf('%s: %d siswa — %d baru, %d berubah, %d sama, %d keluar kelas, %d bentrok.',
            $class->name, $r['total'], $r['created'], $r['updated'], $r['same'], $r['left'], $r['conflicts']));
    }
}

<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    private function own(Request $r, AttendanceSession $s): void
    {
        abort_unless($s->teacher_id === $r->user()->id, 403);
    }

    public function index(Request $request)
    {
        $sessions = AttendanceSession::with('classes:id,name')->where('teacher_id', $request->user()->id)
            ->orderByDesc('opens_at')->limit(100)->get();
        $counts = AttendanceRecord::whereIn('session_id', $sessions->pluck('id'))
            ->selectRaw('session_id, count(*) n')->groupBy('session_id')->pluck('n', 'session_id');

        return Inertia::render('Lms/Guru/Attendance', [
            'sessions' => $sessions->map(fn ($s) => [
                'id' => $s->id, 'title' => $s->title, 'description' => $s->description,
                'opens_at' => AttendanceSession::toWib($s->opens_at), 'closes_at' => AttendanceSession::toWib($s->closes_at),
                'late_after' => AttendanceSession::toWib($s->late_after), 'has_code' => (bool) $s->code_hash, 'require_proof' => (bool) $s->require_proof,
                'class_ids' => $s->classes->pluck('id'), 'classes' => $s->classes->pluck('name'),
                'open' => $s->isOpen(), 'filled' => (int) ($counts[$s->id] ?? 0),
            ]),
            'allClasses' => SchoolClass::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'activeYear' => AcademicYear::active()?->name,
        ]);
    }

    private function validated(Request $request): array
    {
        $d = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'opens_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'closes_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'late_after' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'code' => ['nullable', 'string', 'min:4', 'max:20'],
            'require_proof' => ['boolean'],
            'class_ids' => ['required', 'array', 'min:1'],
            'class_ids.*' => ['integer', Rule::exists('core_classes', 'id')->where('is_active', true)],
        ], ['class_ids.required' => 'Pilih minimal satu rombel.', 'closes_at.after' => 'Waktu tutup harus setelah waktu buka.']);
        $d['opens'] = AttendanceSession::fromWib($d['opens_at']);
        $d['closes'] = AttendanceSession::fromWib($d['closes_at']);
        $d['late'] = AttendanceSession::fromWib($d['late_after'] ?? null);
        if ($d['closes']->lte($d['opens'])) {
            throw ValidationException::withMessages(['closes_at' => 'Waktu tutup harus setelah waktu buka.']);
        }
        if ($d['late'] && ($d['late']->lt($d['opens']) || $d['late']->gt($d['closes']))) {
            throw ValidationException::withMessages(['late_after' => 'Batas terlambat harus di antara waktu buka dan tutup.']);
        }

        return $d;
    }

    public function store(Request $request)
    {
        $year = AcademicYear::active();
        if (! $year) {
            return back()->withErrors(['title' => 'Tahun ajaran aktif belum diatur superadmin.']);
        }
        $d = $this->validated($request);
        $s = AttendanceSession::create([
            'teacher_id' => $request->user()->id, 'academic_year_id' => $year->id, 'title' => trim($d['title']),
            'description' => $d['description'] ?? null, 'opens_at' => $d['opens'], 'closes_at' => $d['closes'],
            'late_after' => $d['late'], 'require_proof' => $request->boolean('require_proof'), 'code_hash' => ! empty($d['code']) ? Hash::make($d['code']) : null,
        ]);
        $s->classes()->sync($d['class_ids']);
        ActivityLog::record('create', 'lms', 'Presensi dibuat: '.$s->title, $s);

        return back()->with('success', 'Presensi dibuat.');
    }

    public function update(Request $request, AttendanceSession $session)
    {
        $this->own($request, $session);
        $d = $this->validated($request);
        $attrs = [
            'title' => trim($d['title']), 'description' => $d['description'] ?? null,
            'opens_at' => $d['opens'], 'closes_at' => $d['closes'], 'late_after' => $d['late'],
            'require_proof' => $request->boolean('require_proof'),
        ];
        if (! empty($d['code'])) {
            $attrs['code_hash'] = Hash::make($d['code']);
        } elseif ($request->boolean('clear_code')) {
            $attrs['code_hash'] = null;
        }
        $session->update($attrs);
        $session->classes()->sync($d['class_ids']);

        return back()->with('success', 'Presensi diperbarui.');
    }

    public function destroy(Request $request, AttendanceSession $session)
    {
        $this->own($request, $session);
        foreach ($session->records()->whereNotNull('photo_path')->pluck('photo_path') as $p) {
            \App\Services\AttendanceProof::forget($p);
        }
        $session->delete();
        ActivityLog::record('delete', 'lms', 'Presensi dihapus: '.$session->title);

        return redirect('/ruang-guru/presensi')->with('success', 'Presensi dihapus.');
    }

    public function show(Request $request, AttendanceSession $session)
    {
        $this->own($request, $session);
        $records = $session->records()->get()->keyBy('student_id');
        $students = User::whereIn('id', $session->studentIds()->merge($records->keys())->unique())->orderBy('name')->get(['id', 'name']);

        $rows = $students->map(function ($u) use ($records) {
            $r = $records[$u->id] ?? null;

            return [
                'id' => $u->id, 'name' => $u->name, 'status' => $r->status ?? 'alpa', 'filled' => (bool) $r,
                'photo' => (bool) ($r->photo_path ?? null), 'lat' => $r->lat ?? null, 'lng' => $r->lng ?? null, 'acc' => $r->accuracy ?? null,
                'note' => $r->note ?? null, 'at' => AttendanceSession::toWib($r->marked_at ?? null), 'by' => $r->marked_by ?? null,
            ];
        })->values();

        return Inertia::render('Lms/Guru/AttendanceShow', [
            'session' => ['id' => $session->id, 'title' => $session->title, 'opens_at' => AttendanceSession::toWib($session->opens_at), 'closes_at' => AttendanceSession::toWib($session->closes_at), 'require_proof' => (bool) $session->require_proof],
            'rows' => $rows,
            'summary' => $rows->groupBy('status')->map->count(),
            'statuses' => AttendanceSession::STATUSES,
        ]);
    }

    /** Guru mengoreksi status siswa (mis. izin yang disampaikan lisan). */
    public function setRecord(Request $request, AttendanceSession $session, User $student)
    {
        $this->own($request, $session);
        abort_unless($student->role === 'siswa' && $student->is_active && $session->hasStudent($student) || $session->records()->where('student_id', $student->id)->exists(), 404);
        $d = $request->validate([
            'status' => ['required', Rule::in(array_keys(AttendanceSession::STATUSES))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        AttendanceRecord::updateOrCreate(
            ['session_id' => $session->id, 'student_id' => $student->id],
            ['status' => $d['status'], 'note' => $d['note'] ?? null, 'marked_at' => now(), 'marked_by' => 'guru']
        );

        return back()->with('success', 'Status diperbarui.');
    }

    /** Selfie hanya bisa dilihat guru pemilik sesi. */
    public function photo(Request $request, AttendanceSession $session, User $student)
    {
        $this->own($request, $session);
        $r = $session->records()->where('student_id', $student->id)->first();
        abort_unless($r && $r->photo_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($r->photo_path), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->response($r->photo_path, null, ['Cache-Control' => 'private, max-age=300', 'Content-Type' => 'image/jpeg']);
    }

    public function export(Request $request, AttendanceSession $session)
    {
        $this->own($request, $session);
        $records = $session->records()->get()->keyBy('student_id');
        $students = User::whereIn('id', $session->studentIds()->merge($records->keys())->unique())->orderBy('name')->get(['id', 'name']);
        $esc = fn ($v) => '"'.str_replace('"', '""', preg_match('/^\s*[=+\-@|\t\r]/', (string) $v) ? "'".$v : (string) $v).'"';
        $out = "\xEF\xBB\xBF".'"No","Nama","Status","Keterangan","Waktu (WIB)","Latitude","Longitude","Akurasi (m)"'."\n";
        foreach ($students as $i => $u) {
            $r = $records[$u->id] ?? null;
            $out .= implode(',', [$i + 1, $esc($u->name), $esc(AttendanceSession::STATUSES[$r->status ?? 'alpa']), $esc($r->note ?? ''), $esc(AttendanceSession::toWib($r->marked_at ?? null) ?? ''), $esc($r->lat ?? ''), $esc($r->lng ?? ''), $esc($r->accuracy ?? '')])."\n";
        }

        return response($out, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="presensi-'.$session->id.'.csv"',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user();
        $sessions = AttendanceSession::with('teacher:id,name')->whereIn('id', AttendanceSession::idsForStudent($me))
            ->where('opens_at', '<=', now()->addDays(7))->orderByDesc('opens_at')->limit(60)->get();
        $mine = AttendanceRecord::where('student_id', $me->id)->whereIn('session_id', $sessions->pluck('id'))->get()->keyBy('session_id');

        return Inertia::render('Lms/Siswa/Attendance', [
            'sessions' => $sessions->map(fn ($s) => [
                'id' => $s->id, 'title' => $s->title, 'description' => $s->description, 'teacher' => $s->teacher?->name,
                'opens_at' => AttendanceSession::toWib($s->opens_at), 'closes_at' => AttendanceSession::toWib($s->closes_at),
                'state' => $s->opens_at->isFuture() ? 'soon' : ($s->closes_at->isPast() ? 'closed' : 'open'),
                'needs_code' => (bool) $s->code_hash,
                'status' => $mine[$s->id]->status ?? ($s->closes_at->isPast() ? 'alpa' : null),
                'locked' => ($mine[$s->id]->marked_by ?? null) === 'guru',
                'note' => $mine[$s->id]->note ?? null,
            ]),
            'statuses' => AttendanceSession::STATUSES,
        ]);
    }

    public function mark(Request $request, AttendanceSession $session)
    {
        $me = $request->user();
        abort_unless($session->hasStudent($me), 403);
        $d = $request->validate([
            'status' => ['required', Rule::in(['hadir', 'izin', 'sakit'])],
            'note' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20'],
        ]);
        if (! $session->isOpen()) {
            return back()->withErrors(['status' => 'Presensi ini sedang tidak dibuka.']);
        }
        $existing = AttendanceRecord::where('session_id', $session->id)->where('student_id', $me->id)->first();
        if ($existing && $existing->marked_by === 'guru') {
            return back()->withErrors(['status' => 'Status sudah diatur guru dan tidak bisa diubah.']);
        }
        if ($d['status'] !== 'hadir' && trim((string) ($d['note'] ?? '')) === '') {
            return back()->withErrors(['note' => 'Isi keterangan untuk izin/sakit.']);
        }
        if ($session->code_hash && ! Hash::check((string) ($d['code'] ?? ''), $session->code_hash)) {
            return back()->withErrors(['code' => 'Kode presensi salah.']);
        }
        $status = $d['status'];
        if ($status === 'hadir' && $session->late_after && now()->gt($session->late_after)) {
            $status = 'terlambat';
        }
        AttendanceRecord::updateOrCreate(
            ['session_id' => $session->id, 'student_id' => $me->id],
            ['status' => $status, 'note' => $status === 'hadir' || $status === 'terlambat' ? null : trim($d['note']), 'marked_at' => now(), 'marked_by' => 'self']
        );

        return back()->with('success', 'Presensi tercatat: '.AttendanceSession::STATUSES[$status].'.');
    }
}

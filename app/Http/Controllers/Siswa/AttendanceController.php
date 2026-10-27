<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Services\AttendanceProof;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
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
                'needs_code' => (bool) $s->code_hash, 'needs_proof' => (bool) $s->require_proof,
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
            'selfie' => ['nullable', 'file', 'max:3072'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);
        $proof = $session->require_proof && in_array($d['status'], ['hadir'], true);
        if ($proof && (! $request->hasFile('selfie') || ! isset($d['lat'], $d['lng']))) {
            return back()->withErrors(['selfie' => 'Presensi ini wajib selfie dan izin lokasi. Aktifkan kamera dan lokasi, lalu coba lagi.']);
        }
        if (! $session->isOpen()) {
            return back()->withErrors(['status' => 'Presensi ini sedang tidak dibuka.']);
        }
        $key = 'att-code:'.$session->id.':'.$me->id;
        if ($session->code_hash && RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => 'Terlalu banyak kode salah. Coba lagi beberapa menit lagi.']);
        }
        if ($d['status'] !== 'hadir' && trim((string) ($d['note'] ?? '')) === '') {
            return back()->withErrors(['note' => 'Isi keterangan untuk izin/sakit.']);
        }
        if ($session->code_hash && ! Hash::check((string) ($d['code'] ?? ''), $session->code_hash)) {
            RateLimiter::hit($key, 300);

            return back()->withErrors(['code' => 'Kode presensi salah.']);
        }
        $photo = $proof ? AttendanceProof::store($request->file('selfie'), $session->id) : null;
        $result = DB::transaction(function () use ($session, $me, $d, $photo, $proof) {
            AttendanceSession::whereKey($session->id)->lockForUpdate()->first();
            $existing = AttendanceRecord::where('session_id', $session->id)->where('student_id', $me->id)->first();
            if ($existing && $existing->marked_by === 'guru') {
                return 'Status sudah diatur guru dan tidak bisa diubah.';
            }
            if ($existing && in_array($existing->status, ['hadir', 'terlambat'], true)) {
                return 'Kehadiran sudah tercatat dan tidak bisa diubah. Hubungi guru bila ada kekeliruan.';
            }
            $status = $d['status'];
            if ($status === 'hadir' && $session->late_after && now()->gt($session->late_after)) {
                $status = 'terlambat';
            }
            $extra = $proof ? ['photo_path' => $photo, 'lat' => $d['lat'], 'lng' => $d['lng'], 'accuracy' => isset($d['accuracy']) ? (int) round($d['accuracy']) : null] : [];
            AttendanceRecord::updateOrCreate(
                ['session_id' => $session->id, 'student_id' => $me->id],
                ['status' => $status, 'note' => in_array($status, ['hadir', 'terlambat'], true) ? null : trim($d['note']), 'marked_at' => now(), 'marked_by' => 'self'] + $extra
            );

            return $status;
        });
        if (! array_key_exists($result, AttendanceSession::STATUSES)) {
            AttendanceProof::forget($photo);
            return back()->withErrors(['status' => $result]);
        }
        $status = $result;

        return back()->with('success', 'Presensi tercatat: '.AttendanceSession::STATUSES[$status].'.');
    }
}

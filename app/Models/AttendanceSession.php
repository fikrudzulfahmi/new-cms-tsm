<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceSession extends Model
{
    public const STATUSES = ['hadir' => 'Hadir', 'terlambat' => 'Terlambat', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa'];

    protected $table = 'lms_attendance_sessions';

    protected $fillable = ['teacher_id', 'academic_year_id', 'title', 'description', 'opens_at', 'closes_at', 'late_after', 'code_hash', 'require_proof'];

    protected $hidden = ['code_hash'];

    protected $casts = ['opens_at' => 'datetime', 'closes_at' => 'datetime', 'late_after' => 'datetime', 'require_proof' => 'boolean'];

    public function classes()
    {
        return $this->belongsToMany(SchoolClass::class, 'lms_attendance_classes', 'session_id', 'class_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function records()
    {
        return $this->hasMany(AttendanceRecord::class, 'session_id');
    }

    public function isOpen(): bool
    {
        return now()->between($this->opens_at, $this->closes_at);
    }

    /** Siswa aktif yang menjadi peserta (anggota rombel terpilih pada tahun ajaran sesi). */
    public function studentIds()
    {
        return DB::table('lms_attendance_classes as ac')
            ->join('core_class_members as m', fn ($j) => $j->on('m.class_id', '=', 'ac.class_id')->where('m.academic_year_id', $this->academic_year_id))
            ->join('users as u', 'u.id', '=', 'm.student_id')->where('u.is_active', true)
            ->where('ac.session_id', $this->id)->whereNull('m.left_at')->distinct()->pluck('m.student_id');
    }

    public function hasStudent(User $u): bool
    {
        return DB::table('lms_attendance_classes as ac')
            ->join('core_class_members as m', fn ($j) => $j->on('m.class_id', '=', 'ac.class_id')->where('m.academic_year_id', $this->academic_year_id))
            ->where('ac.session_id', $this->id)->where('m.student_id', $u->id)->whereNull('m.left_at')->exists();
    }

    public static function idsForStudent(User $u)
    {
        return DB::table('lms_attendance_classes as ac')
            ->join('lms_attendance_sessions as s', 's.id', '=', 'ac.session_id')
            ->join('core_class_members as m', fn ($j) => $j->on('m.class_id', '=', 'ac.class_id')->on('m.academic_year_id', '=', 's.academic_year_id'))
            ->where('m.student_id', $u->id)->whereNull('m.left_at')->distinct()->pluck('s.id');
    }

    /** Konversi input WIB (datetime-local) ke UTC. */
    public static function fromWib(?string $v): ?Carbon
    {
        return $v ? Carbon::createFromFormat('Y-m-d\TH:i', $v, 'Asia/Jakarta')->utc() : null;
    }

    public static function toWib($dt): ?string
    {
        return $dt ? $dt->copy()->setTimezone('Asia/Jakarta')->format('Y-m-d\TH:i') : null;
    }
}

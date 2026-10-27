<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $table = 'lms_courses';

    protected $fillable = ['teacher_id', 'academic_year_id', 'title', 'description', 'leaderboard_enabled', 'is_active'];

    protected $casts = ['leaderboard_enabled' => 'boolean', 'is_active' => 'boolean'];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function year()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function classes()
    {
        return $this->belongsToMany(SchoolClass::class, 'lms_course_classes', 'course_id', 'class_id');
    }

    public function sections()
    {
        return $this->hasMany(CourseSection::class, 'course_id')->orderBy('sort_order')->orderBy('id');
    }

    /** Apakah siswa ini terdaftar (anggota rombel yang ditautkan, pada tahun ajaran mapel ini). */
    public function hasStudent(User $student): bool
    {
        return \DB::table('lms_course_classes as cc')
            ->join('core_class_members as m', function ($j) {
                $j->on('m.class_id', '=', 'cc.class_id')->where('m.academic_year_id', $this->academic_year_id);
            })
            ->where('cc.course_id', $this->id)->where('m.student_id', $student->id)->whereNull('m.left_at')
            ->exists();
    }

    /** ID mapel aktif yang boleh diikuti siswa. */
    public static function idsForStudent(User $student): array
    {
        return \DB::table('lms_course_classes as cc')
            ->join('lms_courses as c', 'c.id', '=', 'cc.course_id')
            ->join('core_class_members as m', function ($j) {
                $j->on('m.class_id', '=', 'cc.class_id')->on('m.academic_year_id', '=', 'c.academic_year_id');
            })
            ->where('c.is_active', true)->where('m.student_id', $student->id)->whereNull('m.left_at')
            ->distinct()->pluck('c.id')->all();
    }
/** Siswa anggota rombel yang ditautkan ke mapel ini. */
    public function students()
    {
        return User::whereIn('id', \DB::table('lms_course_classes as cc')
            ->join('core_class_members as m', function ($j) {
                $j->on('m.class_id', '=', 'cc.class_id')->where('m.academic_year_id', '=', $this->academic_year_id);
            })
            ->where('cc.course_id', $this->id)->whereNull('m.left_at')->pluck('m.student_id'))
            ->orderBy('name')->get(['id', 'name', 'username']);
    }
}

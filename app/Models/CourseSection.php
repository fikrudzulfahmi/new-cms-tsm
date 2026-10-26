<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseSection extends Model
{
    protected $table = 'lms_sections';

    protected $fillable = ['course_id', 'title', 'sort_order', 'is_published'];

    protected $casts = ['is_published' => 'boolean'];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class, 'section_id')->orderBy('sort_order')->orderBy('id');
    }
}

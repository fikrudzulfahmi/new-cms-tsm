<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model
{
    protected $table = 'lms_lesson_progress';

    public $timestamps = false;

    protected $fillable = ['lesson_id', 'student_id', 'completed_at'];

    protected $casts = ['completed_at' => 'datetime'];
}

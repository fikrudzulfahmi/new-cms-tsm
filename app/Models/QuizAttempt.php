<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    protected $table = 'lms_quiz_attempts';

    public $timestamps = false;

    protected $fillable = [
        'quiz_id', 'student_id', 'attempt_no', 'started_at', 'deadline_at', 'submitted_at', 'score', 'status',
        'tab_switch_count', 'ip', 'question_order', 'option_orders',
    ];

    protected $casts = [
        'started_at' => 'datetime', 'deadline_at' => 'datetime', 'submitted_at' => 'datetime',
        'question_order' => 'array', 'option_orders' => 'array', 'score' => 'float',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function answers()
    {
        return $this->hasMany(QuizAnswer::class, 'attempt_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAnswer extends Model
{
    protected $table = 'lms_quiz_answers';

    public $timestamps = false;

    protected $fillable = ['attempt_id', 'question_id', 'answer', 'is_correct', 'points_awarded', 'graded_by', 'feedback'];

    protected $casts = ['answer' => 'array', 'is_correct' => 'boolean'];
}

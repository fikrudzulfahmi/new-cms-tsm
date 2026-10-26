<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $table = 'lms_quizzes';

    protected $fillable = [
        'course_id', 'section_id', 'title', 'description', 'opens_at', 'closes_at', 'duration_min', 'shuffle_questions',
        'shuffle_options', 'max_attempts', 'pass_score', 'access_code_hash', 'show_result', 'is_published',
    ];

    protected $hidden = ['access_code_hash'];

    protected $casts = [
        'opens_at' => 'datetime', 'closes_at' => 'datetime', 'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean', 'is_published' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'lms_quiz_questions', 'quiz_id', 'question_id')
            ->withPivot('sort_order', 'points')->orderBy('lms_quiz_questions.sort_order');
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function isOpen(): bool
    {
        return now()->between($this->opens_at, $this->closes_at);
    }

    public function totalPoints(): float
    {
        return (float) \DB::table('lms_quiz_questions')->where('quiz_id', $this->id)->sum('points');
    }
}

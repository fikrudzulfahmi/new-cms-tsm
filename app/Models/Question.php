<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    public const TYPES = [
        'pg' => 'Pilihan ganda', 'benar_salah' => 'Benar/Salah', 'pg_kompleks' => 'Pilihan ganda kompleks',
        'menjodohkan' => 'Menjodohkan', 'isian' => 'Isian singkat', 'uraian' => 'Uraian',
    ];

    protected $table = 'lms_questions';

    protected $fillable = ['course_id', 'type', 'body', 'explanation', 'points'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class, 'question_id')->orderBy('sort_order')->orderBy('id');
    }

    public function usedInAttemptedQuiz(): bool
    {
        return \DB::table('lms_quiz_questions as qq')
            ->join('lms_quiz_attempts as a', 'a.quiz_id', '=', 'qq.quiz_id')
            ->where('qq.question_id', $this->id)->exists();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionOption extends Model
{
    protected $table = 'lms_question_options';

    public $timestamps = false;

    protected $fillable = ['question_id', 'body', 'match_body', 'is_correct', 'sort_order'];

    protected $casts = ['is_correct' => 'boolean'];
}

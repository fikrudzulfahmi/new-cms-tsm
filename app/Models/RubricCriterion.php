<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RubricCriterion extends Model
{
    protected $table = 'lms_rubric_criteria';

    protected $fillable = ['assignment_id', 'name', 'weight', 'sort_order'];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }
}

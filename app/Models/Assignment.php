<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $table = 'lms_assignments';

    protected $fillable = [
        'course_id', 'section_id', 'title', 'instructions', 'due_at', 'allow_late', 'max_score',
        'is_published', 'attachment_path', 'attachment_name',
    ];

    protected $casts = ['due_at' => 'datetime', 'allow_late' => 'boolean', 'is_published' => 'boolean'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function criteria()
    {
        return $this->hasMany(RubricCriterion::class)->orderBy('sort_order')->orderBy('id');
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    /** Batas akhir efektif (null = tanpa batas). */
    public function isPastDue(): bool
    {
        return $this->due_at !== null && now()->gte($this->due_at);
    }

    public function acceptsSubmission(): bool
    {
        return ! $this->isPastDue() || $this->allow_late;
    }
}

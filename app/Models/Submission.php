<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $table = 'lms_submissions';

    protected $fillable = [
        'assignment_id', 'student_id', 'type', 'text', 'link', 'file_path', 'file_name', 'file_mime',
        'file_size', 'score', 'feedback', 'rubric_scores', 'is_late', 'submitted_at', 'graded_at', 'graded_by',
    ];

    protected $casts = [
        'rubric_scores' => 'array', 'is_late' => 'boolean', 'submitted_at' => 'datetime', 'graded_at' => 'datetime',
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function files()
    {
        return $this->hasMany(SubmissionFile::class)->orderByDesc('id');
    }

    public function isGraded(): bool
    {
        return $this->graded_at !== null;
    }

    /** Nilai akhir dari rubrik: Σ (skor kriteria × bobot) / Σ bobot, diskalakan ke max_score. */
    public function weightedScore(): ?float
    {
        $criteria = $this->assignment->criteria;
        $scores = (array) ($this->rubric_scores ?? []);
        if ($criteria->isEmpty() || $scores === []) {
            return $this->score !== null ? (float) $this->score : null;
        }

        $num = 0.0;
        $den = 0;
        foreach ($criteria as $c) {
            $val = $scores[(string) $c->id] ?? $scores[$c->id] ?? null;
            if ($val === null || $val === '') {
                continue;
            }
            $num += (float) $val * max(1, (int) $c->weight);
            $den += max(1, (int) $c->weight);
        }

        return $den > 0 ? round($num / $den, 2) : null;
    }
}

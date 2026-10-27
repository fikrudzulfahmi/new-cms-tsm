<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionFile extends Model
{
    protected $table = 'lms_submission_files';

    protected $fillable = ['submission_id', 'path', 'name', 'mime', 'size'];

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
}

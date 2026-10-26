<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncRun extends Model
{
    protected $table = 'core_sync_runs';

    protected $fillable = [
        'trigger', 'status', 'academic_year_id', 'classes', 'created', 'updated',
        'deactivated', 'conflicts', 'details', 'message', 'started_at', 'finished_at',
    ];

    protected $casts = ['details' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    protected $table = 'lms_attendance_records';

    public $timestamps = false;

    protected $fillable = ['session_id', 'student_id', 'status', 'note', 'marked_at', 'marked_by', 'photo_path', 'lat', 'lng', 'accuracy'];

    protected $casts = ['marked_at' => 'datetime'];
}

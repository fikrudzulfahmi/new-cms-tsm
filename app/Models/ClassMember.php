<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassMember extends Model
{
    protected $table = 'core_class_members';

    protected $fillable = ['class_id', 'student_id', 'academic_year_id', 'left_at'];

    protected $casts = ['left_at' => 'datetime'];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}

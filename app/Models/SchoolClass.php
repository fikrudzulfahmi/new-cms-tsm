<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    protected $table = 'core_classes';

    protected $fillable = ['external_id', 'name', 'level', 'major', 'is_active', 'synced_at'];

    protected $casts = ['is_active' => 'boolean', 'synced_at' => 'datetime'];

    public function members()
    {
        return $this->hasMany(ClassMember::class, 'class_id');
    }
}

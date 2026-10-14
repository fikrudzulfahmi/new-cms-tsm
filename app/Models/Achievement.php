<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    protected $fillable = ['title', 'student_name', 'level', 'rank', 'organizer', 'year', 'achieved_at', 'description', 'image', 'is_featured', 'sort_order', 'is_active'];
    protected $casts = ['achieved_at' => 'date', 'is_featured' => 'boolean', 'is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order', 'desc');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}

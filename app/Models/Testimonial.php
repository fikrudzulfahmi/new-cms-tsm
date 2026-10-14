<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = ['name', 'graduation_year', 'occupation', 'workplace', 'message', 'rating', 'photo', 'status', 'is_featured'];
    protected $casts = ['is_featured' => 'boolean'];

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}

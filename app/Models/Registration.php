<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    protected $fillable = ['registration_number', 'full_name', 'nisn', 'gender', 'birth_date', 'birth_place', 'address', 'phone', 'email', 'origin_school', 'parent_name', 'parent_phone', 'notes', 'status'];
    protected $casts = ['birth_date' => 'date'];

    public function scopeNew($query)
    {
        return $query->where('status', 'baru');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'diterima');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'ditolak');
    }
}

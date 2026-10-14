<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visit extends Model
{
    protected $fillable = ['url', 'path', 'post_id', 'visitor_hash', 'ip_hash', 'referrer', 'device', 'browser', 'country', 'is_unique', 'visited_on'];
    protected $casts = ['visited_on' => 'date', 'is_unique' => 'boolean'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('visited_on', today());
    }

    public function scopeMonth($query)
    {
        return $query->whereBetween('visited_on', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    public function scopeUnique($query)
    {
        return $query->where('is_unique', true);
    }
}

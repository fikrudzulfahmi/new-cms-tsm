<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    protected $fillable = [
        'category_id', 'user_id', 'title', 'slug', 'excerpt', 'body', 'cover_image', 'cover_alt',
        'status', 'published_at', 'is_featured', 'allow_comments',
        'views_count', 'likes_count', 'dislikes_count', 'shares_count', 'comments_count', 'reading_time',
        'meta_title', 'meta_description', 'focus_keyword', 'canonical_url', 'og_image', 'seo_score', 'seo_report'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'seo_report' => 'json',
        'is_featured' => 'boolean',
        'allow_comments' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tag');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(PostReaction::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->where('status', 'approved');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function ratioLikeDislike()
    {
        $total = $this->likes_count + $this->dislikes_count;
        return $total > 0 ? round(($this->likes_count / $total) * 100) : 0;
    }

}

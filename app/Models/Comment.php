<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    protected $fillable = ['post_id', 'parent_id', 'name', 'email', 'body', 'status', 'ip_hash', 'user_agent'];

    // Email & metadata teknis tidak boleh ikut terkirim ke browser pengunjung.
    protected $hidden = ['email', 'ip_hash', 'user_agent'];

    protected static function booted(): void
    {
        $sync = function (Comment $comment) {
            $post = $comment->post()->first();
            if ($post) {
                $post->updateQuietly([
                    'comments_count' => $post->comments()->approved()->count(),
                ]);
            }
        };

        static::saved($sync);
        static::deleted($sync);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->where('status', 'approved');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(CommentReaction::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }
}

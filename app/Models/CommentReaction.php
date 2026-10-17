<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentReaction extends Model
{
    protected $hidden = ['visitor_hash'];

    protected $fillable = ['comment_id', 'visitor_hash'];
    public $timestamps = true;

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\Model;

class Comment extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'forum_comments';

    // ---------------- Mass assignable fields ----------------
    protected $fillable = [
        'threadId',
        'authorId',
        'content',
        'parentId',
        'path',
        'depth',
        'upvotes',
        'downvotes',
        'replyCount',
        'mentions',
        'status',
        'isEdited',
        'attachments',
        'is_hidden',
        'official_reply',
        'moderation_reason',
        'ai_score',
        'ai_label',
        'ai_reason',
        'ai_reviewed',
        'ai_auto_hidden',
        'ai_hate_score',
        'ai_hate_label',
        'ai_hate_reason',
        'ai_hate_reviewed',
    ];

    // ---------------- Default values ----------------
    protected $attributes = [
        'upvotes' => 0,
        'downvotes' => 0,
        'replyCount' => 0,
        'mentions' => [],
        'attachments' => [],
        'isEdited' => false,
        'is_hidden' => false,
        'official_reply' => false,
        'status' => 'active',
        'ai_score' => null,
        'ai_label' => null,
        'ai_reason' => null,
        'ai_reviewed' => false,
        'ai_auto_hidden' => false,
        'ai_hate_score' => null,
        'ai_hate_label' => null,
        'ai_hate_reason' => null,
        'ai_hate_reviewed' => false,
    ];

    // ---------------- Casts ----------------
    // Remove array casts because MongoDB stores arrays natively
    protected $casts = [
        'createdAt' => 'datetime',
        'updatedAt' => 'datetime',
    ];

    const CREATED_AT = 'createdAt';

    const UPDATED_AT = 'updatedAt';

    public $timestamps = true;

    // ---------------- Relationships ----------------

    // Parent comment
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parentId', '_id');
    }

    // Nested children (1 level)
    public function children(): HasMany
    {
        return $this->hasMany(Comment::class, 'parentId', '_id')->with('user');
    }

    // User who posted comment
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorId', 'id');
    }

    // Thread relationship
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class, 'threadId', 'uuid');
    }
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Comment;
use Laravel\Scout\Searchable;  // added for full-text search

class Thread extends Model
{
    use SoftDeletes, HasFactory, Searchable;

    protected $fillable = [
        'uuid',
        'category_id',
        'user_id',
        'title',
        'slug',
        'content',
        'status',
        'is_pinned',
        'is_locked',
        'views',
        'upvotes',
        'downvotes',
        'comment_count',
        'last_activity_at',
        'best_comment_id'
    ];

    // Define searchable data for Scout
    public function toSearchableArray()
    {
        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'category_id' => (int) $this->category_id,
            // optional: add 'user_id' for user-based searches
        ];
    }

    // ---------------- Slug + boot logic ----------------
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($thread) {
            $thread->uuid = (string) Str::uuid();
            $thread->slug = self::generateUniqueSlug($thread->title);
        });

        static::updating(function ($thread) {
            if ($thread->isDirty('title')) {
                $thread->slug = self::generateUniqueSlug($thread->title, $thread->id);
            }
        });
    }

    public static function generateUniqueSlug($title, $ignoreId = null)
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while (self::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists())
        {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }

    // ---------------- Relationships ----------------
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Category::class);
    }

    // ---------------- MongoDB-safe comments ----------------
    public function fetchComments()
    {
        $comments = Comment::where('threadId', $this->_id)
            ->whereNull('parentId')
            ->with(['user', 'children.user'])
            ->orderBy('createdAt', 'asc')
            ->get();

        // Convert _id fields to string for frontend
        return $comments->map(function ($c) {
            $cArr = $c->toArray();
            $cArr['_id'] = (string) $c->_id;

            if (!empty($cArr['children'])) {
                $cArr['children'] = collect($cArr['children'])->map(function ($child) {
                    $child['_id'] = (string) $child['_id'];
                    return $child;
                })->toArray();
            }

            return $cArr;
        })->toArray();
    }

    
}

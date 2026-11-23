<?php

namespace App\Models;

use App\Models\PlatformUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Platform blog post.
 * Authored by platform staff with blog permissions.
 * status: draft | published
 */
class PlatformBlog extends Model
{
    protected $connection = 'mercatura';
    protected $table = 'blogs';

    protected $fillable = [
        'user_id', 'title', 'slug', 'excerpt',
        'body', 'cover_image', 'status', 'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Check and fill empty slug
     * 
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function ($blog) {
            if (empty($blog->slug)) {
                $blog->slug = Str::slug($blog->title);
            }
        });
    }

    /**
     * Get the user who is blog's author
     * 
     * @return BelongsTo<PlatformUser>
     */
    public function author(): BelongsTo 
    {
        return $this->belongsTo(PlatformUser::class, 'user_id');
    }

    /**
     * Check if it's published
     * 
     * @return bool
     */
    public function isPublished(): bool { return $this->status === 'plublished'; }

    /**
     * Check if it's a draft
     * 
     * @return bool
     */
    public function isDraft(): bool { return $this->status === 'draft'; }
}

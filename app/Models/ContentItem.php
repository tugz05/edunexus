<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContentItem extends Model
{
    protected $fillable = [
        'title',
        'description',
        'type',
        'url',
        'file_path',
        'subject',
        'difficulty',
        'created_by',
    ];

    /**
     * Get the user who created this content item.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the tags for this content item.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ContentTag::class, 'content_item_tag');
    }

    /**
     * Get the user interactions for this content item.
     */
    public function userInteractions()
    {
        return $this->hasMany(UserContentInteraction::class);
    }

    /**
     * Get the users who saved this content item.
     */
    public function savedBy()
    {
        return $this->hasMany(SavedContent::class);
    }
}

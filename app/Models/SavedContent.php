<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedContent extends Model
{
    protected $table = 'saved_contents';

    protected $fillable = [
        'user_id',
        'content_item_id',
    ];

    /**
     * Get the user who saved this content.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the content item that was saved.
     */
    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class);
    }
}

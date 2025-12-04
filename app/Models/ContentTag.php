<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContentTag extends Model
{
    protected $fillable = [
        'name',
    ];

    /**
     * Get the content items that have this tag.
     */
    public function contentItems(): BelongsToMany
    {
        return $this->belongsToMany(ContentItem::class, 'content_item_tag');
    }
}

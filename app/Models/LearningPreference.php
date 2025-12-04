<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningPreference extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'grade_level',
        'subjects',
        'preferred_difficulty',
        'learning_style',
        'goals',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subjects' => 'array', // Automatically cast JSON to array
        ];
    }

    /**
     * Get the user that owns the learning preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

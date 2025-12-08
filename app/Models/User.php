<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'google_id',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the learning preference for the user.
     */
    public function learningPreference()
    {
        return $this->hasOne(LearningPreference::class);
    }

    /**
     * Get the content interactions for the user.
     */
    public function contentInteractions()
    {
        return $this->hasMany(UserContentInteraction::class);
    }

    /**
     * Get the saved content for the user.
     */
    public function savedContent()
    {
        return $this->hasMany(SavedContent::class);
    }

    /**
     * Get the assistant conversations for the user.
     */
    public function assistantConversations()
    {
        return $this->hasMany(AssistantConversation::class);
    }
}

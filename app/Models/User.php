<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use MatanYadaev\EloquentSpatial\Objects\Point;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
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
        'last_location',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
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
            'last_location' => Point::class,
        ];
    }

    public function eventOrganizer()
    {
        return $this->hasOne(EventOrganizer::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function recommendationFeatureSnapshots()
    {
        return $this->hasMany(RecommendationFeatureSnapshot::class);
    }

    // Helper cek role
    public function isEO(): bool
    {
        return $this->role === 'eo';
    }
}

<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use MatanYadaev\EloquentSpatial\Objects\Point;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
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
        'refund_destination_type',
        'refund_destination_provider',
        'refund_destination_channel_code',
        'refund_destination_account_number',
        'refund_destination_account_name',
        'refund_destination_updated_at',
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
            'refund_destination_updated_at' => 'datetime',
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

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    // Helper cek role
    public function isEO(): bool
    {
        return $this->role === 'eo';
    }

    public function hasRefundDestination(): bool
    {
        return filled($this->refund_destination_type)
            && filled($this->refund_destination_provider)
            && filled($this->refund_destination_channel_code)
            && filled($this->refund_destination_account_number)
            && filled($this->refund_destination_account_name);
    }
}

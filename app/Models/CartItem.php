<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'quantity',
        'attendee_details',
    ];

    protected $casts = [
        'attendee_details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Subtotal untuk item ini (harga * quantity)
     */
    public function getSubtotalAttribute(): float
    {
        return (float) ($this->event->price ?? 0) * $this->quantity;
    }

    public function getUserRemainingSlotsAttribute(): ?int
    {
        return $this->event?->getUserRemainingSlots($this->user);
    }

    public function getRequiredAttendeeCountAttribute(): int
    {
        return $this->event ? $this->event->getRequiredAttendeeCount($this->user, $this->quantity) : 0;
    }
}


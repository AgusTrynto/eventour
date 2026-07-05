<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ticket extends Model
{
    protected $fillable = [
        'order_id', 'event_id', 'user_id', 'ticket_code',
        'status', 'checked_in_at', 'checked_in_by',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function checkedInBy()
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function recommendationFeatureSnapshot()
    {
        return $this->hasOne(RecommendationFeatureSnapshot::class);
    }

    // Generate kode unik, contoh: ET-7F3K9X2A1B
    public static function generateCode(): string
    {
        do {
            $code = 'ET-' . strtoupper(Str::random(10));
        } while (self::where('ticket_code', $code)->exists());

        return $code;
    }
}

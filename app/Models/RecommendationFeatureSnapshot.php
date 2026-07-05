<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecommendationFeatureSnapshot extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'order_id',
        'ticket_id',
        'interaction_type',
        'label',
        'event_category',
        'event_price',
        'distance_meters',
        'event_start_at',
        'event_hour',
        'event_day_of_week',
        'is_weekend',
        'order_quantity',
        'paid_at',
        'feature_vector',
        'neural_score',
    ];

    protected $casts = [
        'event_price' => 'decimal:2',
        'distance_meters' => 'decimal:2',
        'event_start_at' => 'datetime',
        'is_weekend' => 'boolean',
        'paid_at' => 'datetime',
        'feature_vector' => 'array',
        'neural_score' => 'decimal:6',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}

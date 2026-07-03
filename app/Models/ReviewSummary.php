<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewSummary extends Model
{
    protected $fillable = [
        'event_id',
        'summary',
        'sentiment',
        'positive_points',
        'negative_points',
        'recommendations',
        'review_count',
        'average_rating',
        'generated_at',
    ];

    protected $casts = [
        'positive_points' => 'array',
        'negative_points' => 'array',
        'recommendations' => 'array',
        'average_rating' => 'decimal:1',
        'generated_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'event_id', 'quantity', 'unit_price', 'total_amount',
        'payment_status', 'payment_method', 'payment_proof',
        'paid_at', 'refunded_at', 'refund_reason', 'refund_requested_at',
        'xendit_invoice_id', 'xendit_invoice_url', 'external_id',
        'xendit_refund_id', 'xendit_refund_reference_id',
        'xendit_refund_status', 'xendit_refund_failure_code',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'refund_requested_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function recommendationFeatureSnapshots()
    {
        return $this->hasMany(RecommendationFeatureSnapshot::class);
    }
}

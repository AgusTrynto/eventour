<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'event_id', 'quantity', 'unit_price', 'total_amount',
        'payment_status', 'payment_method', 'payment_proof',
        'paid_at', 'refunded_at', 'refund_reason', 'refund_requested_at',
        'refund_destination_type', 'refund_destination_provider',
        'refund_destination_account_number', 'refund_destination_account_name',
        'refund_destination_submitted_at', 'manual_refunded_at',
        'manual_refund_proof', 'manual_refund_admin_note',
        'xendit_invoice_id', 'xendit_invoice_url', 'external_id',
        'xendit_refund_id', 'xendit_refund_reference_id',
        'xendit_refund_status', 'xendit_refund_failure_code',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'refund_requested_at' => 'datetime',
        'refund_destination_submitted_at' => 'datetime',
        'manual_refunded_at' => 'datetime',
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public const ADMIN_FEE = 2500;

    protected static function booted(): void
    {
        static::saving(function (Order $order) {
            $subtotal = (float) ($order->attributes['subtotal_amount'] ?? 0);

            if ($subtotal <= 0) {
                $subtotal = (float) ($order->unit_price ?? 0) * (int) ($order->quantity ?? 1);
            }

            if ($subtotal <= 0 && (float) ($order->attributes['total_amount'] ?? 0) > 0) {
                $subtotal = max(0, (float) $order->attributes['total_amount'] - (float) ($order->attributes['admin_fee'] ?? 0));
            }

            $order->attributes['subtotal_amount'] = $subtotal;

            if (! array_key_exists('admin_fee', $order->attributes)) {
                $order->attributes['admin_fee'] = 0;
            }

            if (! array_key_exists('total_amount', $order->attributes) || (float) $order->attributes['total_amount'] <= 0) {
                $order->attributes['total_amount'] = $subtotal + (float) $order->attributes['admin_fee'];
            }
        });
    }

    protected $fillable = [
        'user_id', 'event_id', 'quantity', 'attendee_details', 'unit_price',
        'subtotal_amount', 'admin_fee', 'total_amount',
        'payment_status', 'payment_method', 'payment_proof',
        'paid_at', 'refunded_at', 'refund_reason', 'refund_requested_at',
        'refund_destination_type', 'refund_destination_provider',
        'refund_destination_channel_code', 'refund_destination_account_number',
        'refund_destination_account_name', 'refund_destination_submitted_at',
        'manual_refunded_at',
        'manual_refund_proof', 'manual_refund_admin_note',
        'xendit_invoice_id', 'xendit_invoice_url', 'external_id',
        'xendit_refund_id', 'xendit_refund_reference_id',
        'xendit_refund_status', 'xendit_refund_failure_code',
        'xendit_payout_id', 'xendit_payout_reference_id',
        'xendit_payout_status', 'xendit_payout_failure_code',
        'xendit_payout_requested_at', 'xendit_payout_completed_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'refund_requested_at' => 'datetime',
        'refund_destination_submitted_at' => 'datetime',
        'manual_refunded_at' => 'datetime',
        'xendit_payout_requested_at' => 'datetime',
        'xendit_payout_completed_at' => 'datetime',
        'attendee_details' => 'array',
    ];

    public static function adminFeeForSubtotal(float $subtotal): float
    {
        return $subtotal > 0 ? self::ADMIN_FEE : 0;
    }

    public function getSubtotalAmountAttribute($value): float
    {
        return (float) ($value ?? max(0, (float) $this->total_amount - (float) ($this->admin_fee ?? 0)));
    }

    public function getAdminFeeAttribute($value): float
    {
        return (float) ($value ?? 0);
    }

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

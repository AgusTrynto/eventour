<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    protected $fillable = [
        'event_id', 'event_organizer_id', 'gross_amount', 'platform_fee',
        'net_amount', 'status', 'request_reason', 'request_attachment',
        'requested_at', 'reviewed_at', 'transfer_proof', 'admin_note', 'processed_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function organizer()
    {
        return $this->belongsTo(EventOrganizer::class, 'event_organizer_id');
    }
}

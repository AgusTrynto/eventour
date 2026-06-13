<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'event_organizer_id', 'title', 'description', 'category',
        'start_date', 'end_date', 'location_name', 'lat', 'lng',
        'price', 'quota', 'status', 'reject_reason',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
    ];

    public function organizer()
    {
        return $this->belongsTo(EventOrganizer::class, 'event_organizer_id');
    }
}
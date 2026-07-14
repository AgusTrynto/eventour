<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class EventOrganizer extends Model
{
    use HasSpatial;

    protected $fillable = [
        'user_id', 'org_name', 'phone', 'address', 'status', 'reject_reason',
        'bank_name', 'bank_channel_code', 'bank_account_number', 'bank_account_name', 'location',
    ];

    protected $casts = [
        'location' => Point::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function orders()
    {
        return $this->hasManyThrough(
            Order::class,
            Event::class,
            'event_organizer_id',
            'event_id',
            'id',
            'id'
        );
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }

    public function reviews()
    {
        return $this->hasManyThrough(
            Review::class,
            Event::class,
            'event_organizer_id',
            'event_id',
            'id',
            'id'
        );
    }

    public function getLatAttribute()
    {
        return $this->location?->latitude;
    }

    public function getLngAttribute()
    {
        return $this->location?->longitude;
    }

    public function scopeSelectDistance(Builder $query, float $lat, float $lng): Builder
    {
        return $query
            ->select('event_organizers.*')
            ->addSelect([
                DB::raw("ST_Distance(location, ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)::geography) as distance"),
            ])
            ->orderBy('distance');
    }
}

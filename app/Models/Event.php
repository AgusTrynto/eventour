<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Event extends Model
{
    use HasSpatial;

    protected $fillable = [
        'event_organizer_id',
        'title',
        'description',
        'category',
        'start_date',
        'end_date',
        'location_name',
        'location',
        'price',
        'quota',
        'status',
        'reject_reason',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
        'location'   => Point::class,
    ];

    public function organizer()
    {
        return $this->belongsTo(EventOrganizer::class, 'event_organizer_id');
    }

    /**
     * Scope: cari event dalam radius (meter) dari titik tertentu
     * Usage: Event::nearby($lat, $lng, 10000)->get()
     */
    public function scopeNearby($query, float $lat, float $lng, int $radiusMeters)
    {
        $point = new Point($lat, $lng);

        return $query->whereDistanceSphere('location', $point, '<=', $radiusMeters)
            ->orderByDistanceSphere('location', $point);
    }

    // Helper untuk akses lat/lng seperti sebelumnya
    public function getLatAttribute()
    {
        return $this->location?->latitude;
    }

    public function getLngAttribute()
    {
        return $this->location?->longitude;
    }

    public function getCategoryEmojiAttribute(): string
    {
        return match ($this->category) {
            'musik'     => '🎵',
            'seni'      => '🎭',
            'olahraga'  => '🏃',
            'kuliner'   => '🍜',
            'teknologi' => '🎮',
            default     => '🎪',
        };
    }

    public function scopeSelectDistance(Builder $query, float $lat, float $lng): Builder
    {
        return $query
            ->select('events.*')
            ->addSelect([
                DB::raw("ST_Distance(location, ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)::geography) as distance")
            ])
            ->orderBy('distance');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function payout()
    {
        return $this->hasOne(Payout::class);
    }

    // Total dana yang sudah dibayar user & DITAHAN platform (belum dicairkan)
    public function getEscrowAmountAttribute(): float
    {
        return (float) $this->orders()->where('payment_status', 'paid')->sum('total_amount');
    }

    // Jumlah tiket yang berhasil terjual (status paid)
    public function getTicketsSoldAttribute(): int
    {
        return (int) $this->orders()->where('payment_status', 'paid')->sum('quantity');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function reviewSummary()
    {
        return $this->hasOne(ReviewSummary::class);
    }
}

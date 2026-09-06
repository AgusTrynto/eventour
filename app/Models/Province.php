<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Province extends Model
{
    use HasSpatial;

    protected $fillable = ['name', 'code', 'geom'];

    protected $casts = [
        'geom' => Polygon::class,
    ];

    protected $appends = ['lat', 'lng'];

    public function getLatAttribute(): ?float
    {
        return $this->geom ? $this->geom->getLatitude() : null;
    }

    public function getLngAttribute(): ?float
    {
        return $this->geom ? $this->geom->getLongitude() : null;
    }
}

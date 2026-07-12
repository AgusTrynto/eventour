<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventMapController extends Controller
{
    /**
     * Ambil SEMUA event approved, dengan info jarak (jika lokasi user ada)
     * dan flag apakah event masuk dalam radius yang dipilih.
     */
    public function nearby(Request $request)
    {
        $location = $this->locationFromRequest($request) ?? $this->storedLocation();
        $radius   = (int) $request->input('radius', 10000);

        $query = Event::where('status', 'approved')
            ->notEnded();

        if ($location) {
            // Urutkan dari yang terdekat, sertakan jarak (meter)
            $query->selectDistance($location['lat'], $location['lng']);
        }

        $events = $query->get()->map(function ($e) use ($location, $radius) {
            $distance = $location ? $e->distance : null;

            return [
                'id'        => $e->id,
                'title'     => $e->title,
                'lat'       => $e->lat,
                'lng'       => $e->lng,
                'date'      => $e->start_date?->translatedFormat('d M Y') ?? '-',
                'price'     => $e->price,
                'category'  => $e->category,
                'distance'  => $distance,
                'in_radius' => $distance !== null ? $distance <= $radius : null,
            ];
        });

        return response()->json([
            'events' => $events,
            'count_in_radius' => $events->where('in_radius', true)->count(),
            'count_total' => $events->count(),
        ]);
    }

    private function locationFromRequest(Request $request): ?array
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        if (!is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return compact('lat', 'lng');
    }

    private function storedLocation(): ?array
    {
        $user = Auth::user();

        if ($user?->last_location) {
            return [
                'lat' => (float) $user->last_location->latitude,
                'lng' => (float) $user->last_location->longitude,
            ];
        }

        $location = session('user_location');

        if (! is_array($location)) {
            return null;
        }

        $lat = $location['lat'] ?? null;
        $lng = $location['lng'] ?? null;

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return compact('lat', 'lng');
    }
}

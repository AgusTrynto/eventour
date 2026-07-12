<?php

namespace App\Http\Controllers\EO;


use App\Http\Controllers\Controller;
use App\Models\EventOrganizer;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EOMapController extends Controller
{
    // =========================================================
    // Ambil SEMUA EO approved yang punya lokasi, dengan info jarak
    // (sama pola dengan EventMapController)
    // =========================================================
    public function nearby(Request $request)
    {
        $location = $this->locationFromRequest($request) ?? $this->storedLocation();
        $radius   = (int) $request->input('radius', 10000);

        $query = EventOrganizer::where('status', 'approved')
            ->whereNotNull('location');

        if ($location) {
            $query->selectDistance($location['lat'], $location['lng']);
        }

        $eos = $query->get()->map(function ($eo) use ($location, $radius) {
            $distance = $location ? $eo->distance : null;
            $rating = Review::join('events', 'reviews.event_id', '=', 'events.id')
                ->where('events.event_organizer_id', $eo->id)
                ->selectRaw('AVG(reviews.rating) as average_rating, COUNT(reviews.id) as review_count')
                ->first();

            return [
                'id'             => $eo->id,
                'name'           => $eo->org_name,
                'lat'            => $eo->lat,
                'lng'            => $eo->lng,
                'phone'          => $eo->phone,
                'address'        => $eo->address,
                'total_events'   => $eo->events()->where('status', 'approved')->count(),
                'average_rating' => $rating?->average_rating ? round((float) $rating->average_rating, 1) : null,
                'review_count'   => (int) ($rating?->review_count ?? 0),
                'distance'       => $distance,
                'in_radius'      => $distance !== null ? $distance <= $radius : null,
            ];
        });

        return response()->json([
            'organizers'      => $eos,
            'count_in_radius' => $eos->where('in_radius', true)->count(),
            'count_total'     => $eos->count(),
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

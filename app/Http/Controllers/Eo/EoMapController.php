<?php

namespace App\Http\Controllers\EO;


use App\Http\Controllers\Controller;
use App\Models\EventOrganizer;
use Illuminate\Http\Request;

class EOMapController extends Controller
{
    // =========================================================
    // Ambil SEMUA EO approved yang punya lokasi, dengan info jarak
    // (sama pola dengan EventMapController)
    // =========================================================
    public function nearby(Request $request)
    {
        $location = session('user_location');
        $radius   = (int) $request->input('radius', 10000);

        $query = EventOrganizer::where('status', 'approved')
            ->whereNotNull('location');

        if ($location) {
            $query->selectDistance($location['lat'], $location['lng']);
        }

        $eos = $query->get()->map(function ($eo) use ($location, $radius) {
            $distance = $location ? $eo->distance : null;

            return [
                'id'             => $eo->id,
                'name'           => $eo->org_name,
                'lat'            => $eo->lat,
                'lng'            => $eo->lng,
                'phone'          => $eo->phone,
                'address'        => $eo->address,
                'total_events'   => $eo->events()->where('status', 'approved')->count(),
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
}
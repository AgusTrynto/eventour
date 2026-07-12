<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MatanYadaev\EloquentSpatial\Objects\Point;

class LocationController extends Controller
{
    public function save(Request $request)
    {
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $location = [
            'lat' => (float) $request->lat,
            'lng' => (float) $request->lng,
        ];

        session([
            'user_location' => $location,
        ]);

        Auth::user()->update([
            'last_location' => new Point($location['lat'], $location['lng']),
        ]);

        $request->session()->save();

        return response()->json([
            'status' => 'ok',
            'location' => $location,
        ]);
    }
}

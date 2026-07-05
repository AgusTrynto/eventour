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

        session([
            'user_location' => [
                'lat' => $request->lat,
                'lng' => $request->lng,
            ]
        ]);

        Auth::user()->update([
            'last_location' => new Point((float) $request->lat, (float) $request->lng),
        ]);

        return response()->json(['status' => 'ok']);
    }
}

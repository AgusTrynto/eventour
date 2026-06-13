<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

        return response()->json(['status' => 'ok']);
    }
}
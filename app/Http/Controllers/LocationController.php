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

        // // Opsional: simpan permanen ke kolom users.last_location
        // Auth::user()->update([
        //     'last_location' => new Point($request->lat, $request->lng),
        // ]);

        return response()->json(['status' => 'ok']);
    }
}

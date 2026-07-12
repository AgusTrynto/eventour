<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function show(Event $event)
    {
        if ($event->status !== 'approved') {
            abort(404);
        }

        $event->load('organizer');
        $userLocation = $this->userLocation(Auth::user());

        if ($userLocation !== null) {
            session(['user_location' => $userLocation]);
        }

        return view('events.show', compact('event', 'userLocation'));
    }

    private function userLocation(?User $user): ?array
    {
        if ($user?->last_location) {
            return [
                'lat' => (float) $user->last_location->latitude,
                'lng' => (float) $user->last_location->longitude,
            ];
        }

        $sessionLocation = session('user_location');

        if (! is_array($sessionLocation)) {
            return null;
        }

        $lat = $sessionLocation['lat'] ?? null;
        $lng = $sessionLocation['lng'] ?? null;

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

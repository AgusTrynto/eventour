<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Rekomendasi: event approved terdekat (kalau lokasi ada),
        // fallback: event approved terbaru
        $location = session('user_location');

        if ($location) {
            $recommendedEvents = Event::where('status', 'approved')
                ->nearby($location['lat'], $location['lng'], 50000) // 50km
                ->limit(5)
                ->get();
        } else {
            $recommendedEvents = Event::where('status', 'approved')
                ->where('start_date', '>=', now())
                ->orderBy('start_date', 'asc')
                ->limit(5)
                ->get();
        }

        // Jumlah tiket yang sudah dibayar oleh user (dan belum direfund)
        $ticketCount = Order::where('user_id', $user->id)
            ->where('payment_status', 'paid')
            ->whereNull('refunded_at')
            ->sum('quantity');

        return view('user.dashboard', compact('user', 'recommendedEvents', 'ticketCount'));
    }
}
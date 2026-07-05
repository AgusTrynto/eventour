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

        $eventSearchItems = Event::where('status', 'approved')
            ->orderBy('start_date', 'asc')
            ->get(['id', 'title', 'category', 'start_date', 'location_name', 'price'])
            ->map(function (Event $event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'category' => $event->category ?? 'lainnya',
                    'location_name' => $event->location_name,
                    'start_date' => $event->start_date?->toDateString(),
                    'display_date' => $event->start_date?->translatedFormat('d M Y') ?? '-',
                    'price' => (float) $event->price,
                    'price_label' => $event->price > 0
                        ? 'Rp ' . number_format($event->price, 0, ',', '.')
                        : 'Gratis',
                    'is_ended' => $event->is_ended,
                    'display_status' => $event->display_status,
                    'url' => route('events.show', $event),
                ];
            })
            ->values();

        $eventPriceMax = (int) ceil(((float) $eventSearchItems->max('price')) / 10000) * 10000;
        $eventPriceStep = $eventPriceMax > 0 && $eventPriceMax < 10000 ? 1000 : 10000;

        return view('user.dashboard', compact(
            'user',
            'recommendedEvents',
            'ticketCount',
            'eventSearchItems',
            'eventPriceMax',
            'eventPriceStep'
        ));
    }
}

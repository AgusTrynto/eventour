<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Order;
use App\Models\RecommendationFeatureSnapshot;
use App\Models\User;
use App\Services\NeuralContentRecommendationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $userLocation = $this->userLocation($user);

        if ($userLocation !== null) {
            session(['user_location' => $userLocation]);
        }

        // Jumlah tiket yang sudah dibayar oleh user (dan belum direfund)
        $ticketCount = Order::where('user_id', $user->id)
            ->whereIn('payment_status', ['paid', 'disbursed'])
            ->whereNull('refunded_at')
            ->sum('quantity');

        $eventCount = Order::where('user_id', $user->id)
            ->whereIn('payment_status', ['paid', 'disbursed'])
            ->whereNull('refunded_at')
            ->distinct('event_id')
            ->count('event_id');

        $eventSearchItems = Event::where('status', 'approved')
            ->notEnded()
            ->orderBy('start_date', 'asc')
            ->get(['id', 'title', 'category', 'start_date', 'end_date', 'location_name', 'location', 'price', 'status'])
            ->map(function (Event $event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'category' => $event->category ?? 'lainnya',
                    'location_name' => $event->location_name,
                    'lat' => $event->lat,
                    'lng' => $event->lng,
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
            'ticketCount',
            'eventCount',
            'eventSearchItems',
            'eventPriceMax',
            'eventPriceStep',
            'userLocation'
        ));
    }

    public function recommendations(NeuralContentRecommendationService $recommendationService)
    {
        $user = Auth::user();
        $cacheKey = $this->recommendationCacheKey($user);
        $html = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($recommendationService, $user) {
            $recommendedEvents = $recommendationService->recommendForUser($user, 3);

            return view('user.partials.recommendations', compact('recommendedEvents'))->render();
        });

        return response()->json([
            'html' => $html,
        ]);
    }

    private function recommendationCacheKey(User $user): string
    {
        $modelPath = (string) config('recommendation.h5.model_path');
        $modelVersion = is_file($modelPath) ? (string) filemtime($modelPath) : 'no-model';
        $userVersion = (string) ($user->updated_at?->timestamp ?? 'no-user-update');
        $snapshotVersion = (string) (
            RecommendationFeatureSnapshot::query()
                ->where('user_id', $user->id)
                ->max('updated_at') ?? 'no-snapshots'
        );

        return 'dashboard:recommendations:' . implode(':', [
            $user->id,
            $modelVersion,
            $userVersion,
            md5($snapshotVersion),
        ]);
    }

    private function userLocation(User $user): ?array
    {
        if ($user->last_location) {
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

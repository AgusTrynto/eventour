<?php

namespace App\Http\Controllers\EO;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\Review;
use App\Models\ReviewSummary;
use App\Services\ReviewSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Throwable;

class EODashboardController extends Controller
{
    // =========================================================
    // Halaman utama EO — cek status dulu
    // =========================================================
    public function index()
    {
        $organizer = Auth::user()->eventOrganizer;

        if (!$organizer) {
            abort(403, 'Akun ini tidak terdaftar sebagai Event Organizer.');
        }

        if ($organizer->status !== 'approved') {
            return view('eo.status', compact('organizer'));
        }

        $approvedEvents = $organizer->events()
            ->where('status', 'approved')
            ->with('payout')
            ->orderBy('start_date', 'asc')
            ->get();

        $pendingEvents = $organizer->events()
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        $rejectedEvents = $organizer->events()
            ->where('status', 'rejected')
            ->orderBy('created_at', 'desc')
            ->get();

        $eventIds = $organizer->events()->pluck('id');

        $paidStatuses = ['paid', 'disbursed'];

        $ticketSoldCount = Order::whereIn('event_id', $eventIds)
            ->whereIn('payment_status', $paidStatuses)
            ->sum('quantity');

        $grossRevenue = Order::whereIn('event_id', $eventIds)
            ->whereIn('payment_status', $paidStatuses)
            ->sum('total_amount');

        $escrowAmount = Order::whereIn('event_id', $eventIds)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        $processingPayoutAmount = $organizer->payouts()
            ->whereIn('status', ['pending', 'processing'])
            ->sum('net_amount');

        $completedPayoutAmount = $organizer->payouts()
            ->where('status', 'completed')
            ->sum('net_amount');

        $readyForPayoutEvents = $organizer->events()
            ->where('status', 'approved')
            ->where('end_date', '<', now())
            ->whereDoesntHave('payout')
            ->orderBy('end_date', 'desc')
            ->get()
            ->filter(fn ($event) => $event->escrow_amount > 0);

        $recentPayouts = $organizer->payouts()
            ->with('event')
            ->latest()
            ->take(8)
            ->get();

        return view('eo.dashboard', compact(
            'organizer',
            'approvedEvents',
            'pendingEvents',
            'rejectedEvents',
            'ticketSoldCount',
            'grossRevenue',
            'escrowAmount',
            'processingPayoutAmount',
            'completedPayoutAmount',
            'readyForPayoutEvents',
            'recentPayouts'
        ));
    }

    // =========================================================
    // Form tambah event
    // =========================================================
    public function createEvent()
    {
        $organizer = Auth::user()->eventOrganizer;

        if (!$organizer || $organizer->status !== 'approved') {
            abort(403, 'Akun EO kamu belum disetujui.');
        }

        return view('eo.create-event', compact('organizer'));
    }

    // =========================================================
    // Simpan event baru (status selalu 'pending' menunggu admin)
    // =========================================================
    public function storeEvent(Request $request)
    {
        $organizer = Auth::user()->eventOrganizer;

        if (!$organizer || $organizer->status !== 'approved') {
            abort(403, 'Akun EO kamu belum disetujui.');
        }

        $request->validate([
            'title'         => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'category'      => ['nullable', 'string', 'max:100'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['nullable', 'date', 'after_or_equal:start_date'],
            'location_name' => ['required', 'string', 'max:255'],
            'lat'           => ['required', 'numeric', 'between:-90,90'],
            'lng'           => ['required', 'numeric', 'between:-180,180'],
            'price'         => ['required', 'numeric', 'min:0'],
            'quota'         => ['nullable', 'integer', 'min:1'],
        ], [
            'title.required'         => 'Nama event wajib diisi.',
            'start_date.required'    => 'Tanggal mulai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'location_name.required' => 'Nama lokasi wajib diisi.',
            'lat.required'           => 'Titik lokasi di map wajib dipilih.',
            'lng.required'           => 'Titik lokasi di map wajib dipilih.',
            'price.required'         => 'Harga tiket wajib diisi (isi 0 jika gratis).',
        ]);

        Event::create([
            'event_organizer_id' => $organizer->id,
            'title'         => $request->title,
            'description'   => $request->description,
            'category'      => $request->category,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'location_name' => $request->location_name,
            // ── PostGIS: simpan sebagai Point, bukan lat/lng terpisah ──
            'location'      => new Point((float) $request->lat, (float) $request->lng),
            'price'         => $request->price,
            'quota'         => $request->quota,
            'status'        => 'pending',
        ]);

        return redirect()->route('eo.dashboard')
            ->with('success', 'Event berhasil diajukan! Menunggu persetujuan admin.');
    }

    // =========================================================
    // Daftar ulasan untuk 1 event milik EO
    // =========================================================
    public function eventReviews(Event $event)
    {
        $organizer = Auth::user()->eventOrganizer;

        if (!$organizer || $event->event_organizer_id !== $organizer->id) {
            abort(403, 'Event ini bukan milikmu.');
        }

        $reviewQuery = Review::where('event_id', $event->id);

        $reviewCount = (clone $reviewQuery)->count();
        $averageRating = $reviewCount > 0
            ? round((clone $reviewQuery)->avg('rating'), 1)
            : null;

        $reviews = $reviewQuery
            ->with('user')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $reviewSummary = $event->reviewSummary;

        return view('eo.event-reviews', compact('event', 'reviews', 'averageRating', 'reviewCount', 'reviewSummary'));
    }

    public function refreshReviewSummary(Event $event, ReviewSummaryService $summaryService)
    {
        $organizer = Auth::user()->eventOrganizer;

        if (!$organizer || $event->event_organizer_id !== $organizer->id) {
            abort(403, 'Event ini bukan milikmu.');
        }

        $reviews = Review::where('event_id', $event->id)
            ->latest()
            ->get();

        if ($reviews->isEmpty()) {
            return back()->with('error', 'Belum ada ulasan yang bisa diringkas.');
        }

        try {
            $analysis = $summaryService->summarize($event, $reviews);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', $exception->getMessage());
        }

        ReviewSummary::updateOrCreate(
            ['event_id' => $event->id],
            [
                'summary' => $analysis['summary'],
                'sentiment' => $analysis['sentiment'],
                'positive_points' => $analysis['positive_points'],
                'negative_points' => $analysis['negative_points'],
                'recommendations' => $analysis['recommendations'],
                'review_count' => $reviews->count(),
                'average_rating' => round($reviews->avg('rating'), 1),
                'generated_at' => now(),
            ]
        );

        return back()->with('success', 'Kesimpulan AI berhasil diperbarui.');
    }
}

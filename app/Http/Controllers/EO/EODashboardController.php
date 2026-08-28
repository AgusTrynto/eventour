<?php

namespace App\Http\Controllers\EO;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\Review;
use App\Models\ReviewSummary;
use App\Services\EOPayoutAutomationService;
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
        $organizer = $this->currentOrganizer();

        if ($organizer->status !== 'approved') {
            return view('eo.status', compact('organizer'));
        }

        app(EOPayoutAutomationService::class)->createDuePayouts($organizer->id);

        $eventIds = $organizer->events()->pluck('id');
        $paidStatuses = ['paid', 'disbursed'];

        $approvedEventCount = $organizer->events()
            ->where('status', 'approved')
            ->count();

        $pendingEventCount = $organizer->events()
            ->where('status', 'pending')
            ->count();

        $rejectedEventCount = $organizer->events()
            ->where('status', 'rejected')
            ->count();

        $ticketSoldCount = Order::whereIn('event_id', $eventIds)
            ->whereIn('payment_status', $paidStatuses)
            ->sum('quantity');

        $grossRevenue = Order::whereIn('event_id', $eventIds)
            ->whereIn('payment_status', $paidStatuses)
            ->sum('subtotal_amount');

        $escrowAmount = Order::whereIn('event_id', $eventIds)
            ->where('payment_status', 'paid')
            ->sum('subtotal_amount');

        $processingPayoutAmount = $organizer->payouts()
            ->whereIn('status', ['pending', 'processing'])
            ->sum('net_amount');

        $completedPayoutAmount = $organizer->payouts()
            ->where('status', 'completed')
            ->sum('net_amount');

        $readyForPayoutCount = $organizer->events()
            ->where('status', 'approved')
            ->whereDoesntHave('payouts', function ($query) {
                $query->whereIn('status', ['pending', 'processing', 'completed', 'failed']);
            })
            ->get()
            ->filter(fn ($event) => $event->escrow_amount > 0 && $event->hasEnded())
            ->count();

        return view('eo.dashboard', compact(
            'organizer',
            'approvedEventCount',
            'pendingEventCount',
            'rejectedEventCount',
            'ticketSoldCount',
            'grossRevenue',
            'escrowAmount',
            'processingPayoutAmount',
            'completedPayoutAmount',
            'readyForPayoutCount'
        ));
    }

public function events(Request $request)
    {
        $organizer = $this->approvedOrganizer();

        $timeFilter = $request->query('time_filter', 'all'); // all, ongoing, upcoming, ended

        $approvedEventsQuery = $organizer->events()
            ->where('status', 'approved')
            ->with('payout');

        if ($timeFilter === 'ongoing') {
            $approvedEventsQuery->where(function ($q) {
                $q->where('start_date', '<=', now())
                  ->where(function ($q2) {
                      $q2->where('end_date', '>=', now())
                         ->orWhereNull('end_date');
                  });
            });
        } elseif ($timeFilter === 'upcoming') {
            $approvedEventsQuery->where('start_date', '>', now());
        } elseif ($timeFilter === 'ended') {
            $approvedEventsQuery->where(function ($q) {
                $q->where('end_date', '<', now())
                  ->orWhere(function ($q2) {
                      $q2->whereNull('end_date')
                         ->where('start_date', '<', now());
                  });
            });
        }

        $approvedEvents = $approvedEventsQuery->orderBy('start_date', 'asc')->get();

        $pendingEvents = $organizer->events()
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        $rejectedEvents = $organizer->events()
            ->where('status', 'rejected')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('eo.events', compact(
            'organizer',
            'approvedEvents',
            'pendingEvents',
            'rejectedEvents',
            'timeFilter'
        ));
    }

    public function payouts()
    {
        $organizer = $this->approvedOrganizer();

        app(EOPayoutAutomationService::class)->createDuePayouts($organizer->id);

        $eventIds = $organizer->events()->pluck('id');
        $paidStatuses = ['paid', 'disbursed'];

        $grossRevenue = Order::whereIn('event_id', $eventIds)
            ->whereIn('payment_status', $paidStatuses)
            ->sum('subtotal_amount');

        $escrowAmount = Order::whereIn('event_id', $eventIds)
            ->where('payment_status', 'paid')
            ->sum('subtotal_amount');

        $processingPayoutAmount = $organizer->payouts()
            ->whereIn('status', ['pending', 'processing'])
            ->sum('net_amount');

        $completedPayoutAmount = $organizer->payouts()
            ->where('status', 'completed')
            ->sum('net_amount');

        $scheduledPayoutEvents = $organizer->events()
            ->where('status', 'approved')
            ->whereDoesntHave('payouts', function ($query) {
                $query->whereIn('status', ['pending', 'processing', 'completed', 'failed']);
            })
            ->orderBy('start_date', 'asc')
            ->get()
            ->filter(fn ($event) => $event->escrow_amount > 0 && ! $event->hasEnded());

        $recentPayouts = $organizer->payouts()
            ->with('event')
            ->latest()
            ->take(8)
            ->get();

        return view('eo.payouts', compact(
            'organizer',
            'grossRevenue',
            'escrowAmount',
            'processingPayoutAmount',
            'completedPayoutAmount',
            'scheduledPayoutEvents',
            'recentPayouts'
        ));
    }

    public function customers()
    {
        $organizer = $this->approvedOrganizer();

        $eventIds = $organizer->events()->pluck('id');
        $paidStatuses = ['paid', 'disbursed'];

        $ticketSoldCount = Order::whereIn('event_id', $eventIds)
            ->whereIn('payment_status', $paidStatuses)
            ->sum('quantity');

        $grossRevenue = Order::whereIn('event_id', $eventIds)
            ->whereIn('payment_status', $paidStatuses)
            ->sum('subtotal_amount');

        $topSpenders = Order::whereIn('event_id', $eventIds)
            ->whereIn('payment_status', $paidStatuses)
            ->select('user_id')
            ->selectRaw('SUM(total_amount) as total_spent')
            ->selectRaw('SUM(quantity) as tickets_bought')
            ->selectRaw('COUNT(*) as orders_count')
            ->with('user')
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->orderByDesc('tickets_bought')
            ->take(5)
            ->get();

        if ($topSpenders->isNotEmpty()) {
            $maxSpent = max(1, (float) $topSpenders->max('total_spent'));

            $topSpenders->each(function (Order $spender) use ($maxSpent) {
                $totalSpent = (float) $spender->total_spent;

                $spender->spend_share_percent = min(100, max(7, (int) round(($totalSpent / $maxSpent) * 100)));
            });
        }

        return view('eo.customers', compact(
            'organizer',
            'ticketSoldCount',
            'grossRevenue',
            'topSpenders'
        ));
    }

    // =========================================================
    // Form tambah event
    // =========================================================
    public function createEvent()
    {
        $organizer = Auth::user()->eventOrganizer;

        if (! $organizer || $organizer->status !== 'approved') {
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

        if (! $organizer || $organizer->status !== 'approved') {
            abort(403, 'Akun EO kamu belum disetujui.');
        }

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'location_name' => ['required', 'string', 'max:255'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'price' => ['required', 'numeric', 'min:0'],
            'quota' => ['nullable', 'integer', 'min:1'],
            'max_tickets_per_person' => ['nullable', 'integer', 'min:1'],
            'ticket_purchase_policy' => ['required', 'in:strict,flexible'],
        ], [
            'title.required' => 'Nama event wajib diisi.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'location_name.required' => 'Nama lokasi wajib diisi.',
            'lat.required' => 'Titik lokasi di map wajib dipilih.',
            'lng.required' => 'Titik lokasi di map wajib dipilih.',
            'price.required' => 'Harga tiket wajib diisi (isi 0 jika gratis).',
            'ticket_purchase_policy.required' => 'Pilih kebijakan pembelian tiket.',
            'ticket_purchase_policy.in' => 'Kebijakan pembelian tiket tidak valid.',
        ]);

        Event::create([
            'event_organizer_id' => $organizer->id,
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'location_name' => $request->location_name,
            // ── PostGIS: simpan sebagai Point, bukan lat/lng terpisah ──
            'location' => new Point((float) $request->lat, (float) $request->lng),
            'price' => $request->price,
            'quota' => $request->quota,
            'max_tickets_per_person' => $request->max_tickets_per_person,
            'ticket_purchase_policy' => $request->ticket_purchase_policy,
            'status' => 'pending',
        ]);

        return redirect()->route('eo.events.index')
            ->with('success', 'Event berhasil diajukan! Menunggu persetujuan admin.');
    }

    // =========================================================
    // Daftar ulasan untuk 1 event milik EO
    // =========================================================
    public function eventReviews(Event $event)
    {
        $organizer = Auth::user()->eventOrganizer;

        if (! $organizer || $event->event_organizer_id !== $organizer->id) {
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

        if (! $organizer || $event->event_organizer_id !== $organizer->id) {
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

    private function currentOrganizer()
    {
        $organizer = Auth::user()->eventOrganizer;

        if (! $organizer) {
            abort(403, 'Akun ini tidak terdaftar sebagai Event Organizer.');
        }

        return $organizer;
    }

    private function approvedOrganizer()
    {
        $organizer = $this->currentOrganizer();

        if ($organizer->status !== 'approved') {
            abort(403, 'Akun EO kamu belum disetujui.');
        }

        return $organizer;
    }
}

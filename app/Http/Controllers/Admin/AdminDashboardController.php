<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventOrganizer;
use App\Models\Event;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users'      => User::where('role', 'user')->count(),
            'total_eo'         => EventOrganizer::count(),
            'pending_eo'       => EventOrganizer::where('status', 'pending')->count(),
            'total_events'     => Event::count(),
            'pending_events'   => Event::where('status', 'pending')->count(),
            'approved_events'  => Event::where('status', 'approved')->count(),
        ];

        $recentPendingEO = EventOrganizer::where('status', 'pending')
            ->with('user')
            ->latest()
            ->take(5)
            ->get();

        $recentPendingEvents = Event::where('status', 'pending')
            ->with('organizer')
            ->latest()
            ->take(5)
            ->get();

        $paidStatuses = ['paid', 'disbursed'];

        $topOrganizers = EventOrganizer::where('status', 'approved')
            ->with('user')
            ->withCount([
                'events as approved_events_count' => fn ($query) => $query->where('status', 'approved'),
                'orders as paid_orders_count' => fn ($query) => $query->whereIn('payment_status', $paidStatuses),
            ])
            ->withSum([
                'orders as tickets_sold_count' => fn ($query) => $query->whereIn('payment_status', $paidStatuses),
            ], 'quantity')
            ->withSum([
                'orders as revenue_total' => fn ($query) => $query->whereIn('payment_status', $paidStatuses),
            ], 'total_amount')
            ->orderByDesc('tickets_sold_count')
            ->orderBy('org_name')
            ->take(5)
            ->get()
            ->filter(fn (EventOrganizer $organizer) => (int) $organizer->tickets_sold_count > 0)
            ->values();

        if ($topOrganizers->isNotEmpty()) {
            $topEventsByOrganizer = Event::whereIn('event_organizer_id', $topOrganizers->pluck('id'))
                ->withSum([
                    'orders as tickets_sold_count' => fn ($query) => $query->whereIn('payment_status', $paidStatuses),
                ], 'quantity')
                ->orderByDesc('tickets_sold_count')
                ->orderByDesc('start_date')
                ->get()
                ->filter(fn (Event $event) => (int) $event->tickets_sold_count > 0)
                ->groupBy('event_organizer_id')
                ->map(fn ($events) => $events->first());

            $maxTicketsSold = max(1, (int) $topOrganizers->max('tickets_sold_count'));

            $topOrganizers->each(function (EventOrganizer $organizer) use ($topEventsByOrganizer, $maxTicketsSold) {
                $ticketsSold = (int) $organizer->tickets_sold_count;

                $organizer->ticket_share_percent = min(100, max(6, (int) round(($ticketsSold / $maxTicketsSold) * 100)));
                $organizer->setRelation('topSellingEvent', $topEventsByOrganizer->get($organizer->id));
            });
        }

        return view('admin.dashboard', compact('stats', 'recentPendingEO', 'recentPendingEvents', 'topOrganizers'));
    }
}

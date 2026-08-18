<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\XenditRefundPayoutService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    // =========================================================
    // List semua tiket milik user yang login
    // =========================================================
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', '');
        $sort = $request->input('sort', 'newest');

        $ticketsQuery = Ticket::where('user_id', Auth::id())
            ->with(['event', 'order', 'user']);

        if ($search !== '') {
            $ticketsQuery->where(function ($q) use ($search) {
                $q->where('ticket_code', 'like', "%{$search}%")
                  ->orWhere('attendee_name', 'like', "%{$search}%")
                  ->orWhereHas('event', fn ($q2) => $q2->where('title', 'like', "%{$search}%"));
            });
        }

        if ($status !== '' && in_array($status, ['valid', 'used', 'cancelled'], true)) {
            $ticketsQuery->where('status', $status);
        }

        switch ($sort) {
            case 'oldest':
                $ticketsQuery->oldest();
                break;
            case 'event_upcoming':
                $ticketsQuery
                    ->join('events', 'events.id', '=', 'tickets.event_id')
                    ->select('tickets.*')
                    ->orderBy('events.start_date', 'asc');
                break;
            case 'event_past':
                $ticketsQuery
                    ->join('events', 'events.id', '=', 'tickets.event_id')
                    ->select('tickets.*')
                    ->orderBy('events.start_date', 'desc');
                break;
            case 'name_asc':
                $ticketsQuery
                    ->join('events', 'events.id', '=', 'tickets.event_id')
                    ->select('tickets.*')
                    ->orderBy('events.title', 'asc');
                break;
            default:
                $ticketsQuery->latest();
        }

        $tickets = $ticketsQuery->get();

        $this->syncVisibleRefundPayouts($tickets);

        $ticketGroups = $tickets
            ->groupBy(fn (Ticket $ticket) => $ticket->event_id.'|'.$this->normalizedHolderName($ticket))
            ->map(fn ($group) => (object) [
                'ticket' => $group->first(),
                'holder_name' => $this->ticketHolderName($group->first()),
                'tickets' => $group->values(),
            ])
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 12;
        $paginator = new LengthAwarePaginator(
            $ticketGroups->forPage($page, $perPage)->values(),
            $ticketGroups->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $ticketGroups = $paginator->getCollection();

        return view('tickets.index', compact('ticketGroups', 'paginator'));
    }

    // =========================================================
    // Detail 1 tiket — tampilkan QR besar untuk di-scan
    // =========================================================
    public function show(Ticket $ticket)
    {
        if ($ticket->user_id !== Auth::id()) {
            abort(403);
        }

        $ticket->load(['event', 'order', 'user']);

        $this->syncTicketRefundPayout($ticket);

        $holderName = $this->ticketHolderName($ticket);
        $normalizedHolderName = Str::lower($holderName);
        $authUserName = Str::lower(trim((string) Auth::user()?->name));

        $holderTickets = Ticket::where('user_id', Auth::id())
            ->where('event_id', $ticket->event_id)
            ->with(['event', 'order', 'user'])
            ->where(function ($query) use ($normalizedHolderName, $authUserName) {
                $query->whereRaw('LOWER(TRIM(attendee_name)) = ?', [$normalizedHolderName]);

                if ($normalizedHolderName === $authUserName) {
                    $query->orWhereNull('attendee_name')
                        ->orWhere('attendee_name', '');
                }
            })
            ->oldest('id')
            ->get();

        $this->syncVisibleRefundPayouts($holderTickets);

        return view('tickets.show', compact('ticket', 'holderTickets', 'holderName'));
    }

    private function ticketHolderName(Ticket $ticket): string
    {
        return trim((string) $ticket->attendee_name)
            ?: ($ticket->user?->name ?? 'Pemegang tiket');
    }

    private function normalizedHolderName(Ticket $ticket): string
    {
        return Str::lower($this->ticketHolderName($ticket));
    }

    private function syncVisibleRefundPayouts($tickets): void
    {
        $payoutService = app(XenditRefundPayoutService::class);

        $tickets->flatten()
            ->pluck('order')
            ->filter(fn ($order) => $order
                && $order->payment_status === 'refund_payout_pending'
                && ($order->xendit_payout_id || $order->xendit_payout_reference_id))
            ->unique('id')
            ->each(function ($order) use ($payoutService) {
                try {
                    $payoutService->syncStatusFromXendit($order);
                    $order->refresh();
                } catch (\Throwable $e) {
                    Log::warning('Unable to sync visible refund payout status', [
                        'order_id' => $order->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            });
    }

    private function syncTicketRefundPayout(Ticket $ticket): void
    {
        $order = $ticket->order;

        if (! $order
            || $order->payment_status !== 'refund_payout_pending'
            || (! $order->xendit_payout_id && ! $order->xendit_payout_reference_id)
        ) {
            return;
        }

        try {
            app(XenditRefundPayoutService::class)->syncStatusFromXendit($order);
            $ticket->load('event', 'order');
        } catch (\Throwable $e) {
            Log::warning('Unable to sync ticket refund payout status', [
                'order_id' => $order->id,
                'ticket_id' => $ticket->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}

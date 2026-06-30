<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    // =========================================================
    // List semua tiket milik user yang login
    // =========================================================
    public function index()
    {
        $tickets = Ticket::where('user_id', Auth::id())
            ->with('event', 'order')
            ->latest()
            ->get()
            ->groupBy('order_id'); // kelompokkan per transaksi

        return view('tickets.index', compact('tickets'));
    }

    // =========================================================
    // Detail 1 tiket — tampilkan QR besar untuk di-scan
    // =========================================================
    public function show(Ticket $ticket)
    {
        if ($ticket->user_id !== Auth::id()) {
            abort(403);
        }

        $ticket->load('event', 'order');

        return view('tickets.show', compact('ticket'));
    }
}
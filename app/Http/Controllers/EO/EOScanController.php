<?php

namespace App\Http\Controllers\EO;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EOScanController extends Controller
{
    // =========================================================
    // Halaman kamera scan QR (untuk satu event tertentu)
    // =========================================================
    public function index()
    {
        $organizer = Auth::user()->eventOrganizer;

        if (!$organizer || $organizer->status !== 'approved') {
            abort(403);
        }

        $events = $organizer->events()
            ->where('status', 'approved')
            ->orderBy('start_date', 'desc')
            ->get();

        return view('eo.scan', compact('events'));
    }

    // =========================================================
    // Validasi kode tiket (dipanggil via fetch/AJAX dari kamera scan)
    // =========================================================
    public function validateTicket(Request $request)
    {
        $request->validate([
            'ticket_code' => ['required', 'string'],
            'event_id'    => ['required', 'integer'],
        ]);

        $organizer = Auth::user()->eventOrganizer;

        $ticket = Ticket::where('ticket_code', $request->ticket_code)
            ->with('event', 'user')
            ->first();

        // Tiket tidak ditemukan sama sekali
        if (!$ticket) {
            return response()->json([
                'valid'   => false,
                'message' => 'Kode tiket tidak ditemukan.',
            ], 404);
        }

        // Tiket bukan untuk event yang sedang di-scan
        if ($ticket->event_id != $request->event_id) {
            return response()->json([
                'valid'   => false,
                'message' => 'Tiket ini bukan untuk event yang sedang dipilih.',
            ], 400);
        }

        // Pastikan event ini benar milik EO yang login (security check)
        if ($ticket->event->event_organizer_id !== $organizer->id) {
            return response()->json([
                'valid'   => false,
                'message' => 'Tiket ini bukan milik event kamu.',
            ], 403);
        }

        // Tiket sudah dipakai sebelumnya
        if ($ticket->status === 'used') {
            return response()->json([
                'valid'   => false,
                'message' => "Tiket sudah digunakan pada {$ticket->checked_in_at->translatedFormat('d M Y, H:i')}.",
                'ticket'  => [
                    'holder' => $ticket->user->name,
                    'code'   => $ticket->ticket_code,
                ],
            ], 409);
        }

        // Tiket dibatalkan/refund
        if ($ticket->status === 'cancelled') {
            return response()->json([
                'valid'   => false,
                'message' => 'Tiket ini sudah dibatalkan.',
            ], 410);
        }

        // ✅ Valid — tandai sebagai used
        $ticket->update([
            'status'        => 'used',
            'checked_in_at' => now(),
            'checked_in_by' => Auth::id(),
        ]);

        return response()->json([
            'valid'   => true,
            'message' => 'Tiket valid! Selamat datang.',
            'ticket'  => [
                'holder' => $ticket->user->name,
                'code'   => $ticket->ticket_code,
                'event'  => $ticket->event->title,
            ],
        ]);
    }
}
<?php

namespace App\Http\Controllers\EO;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MatanYadaev\EloquentSpatial\Objects\Point;

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

        return view('eo.dashboard', compact(
            'organizer', 'approvedEvents', 'pendingEvents', 'rejectedEvents'
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
}
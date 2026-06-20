<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventOrganizer;
use Illuminate\Http\Request;

class AdminEOController extends Controller
{
    public function index()
    {
        $pendingEO  = EventOrganizer::where('status', 'pending')->with('user')->latest()->get();
        $approvedEO = EventOrganizer::where('status', 'approved')->with('user')->latest()->get();
        $rejectedEO = EventOrganizer::where('status', 'rejected')->with('user')->latest()->get();

        return view('admin.eo', compact('pendingEO', 'approvedEO', 'rejectedEO'));
    }

    public function approve(EventOrganizer $organizer)
    {
        $organizer->update(['status' => 'approved']);

        return back()->with('success', "EO \"{$organizer->org_name}\" berhasil disetujui.");
    }

    public function reject(Request $request, EventOrganizer $organizer)
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $organizer->update([
            'status'        => 'rejected',
            'reject_reason' => $request->reason,
        ]);

        return back()->with('success', "EO \"{$organizer->org_name}\" telah ditolak.");
    }
}
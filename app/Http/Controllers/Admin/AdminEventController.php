<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class AdminEventController extends Controller
{
    public function index()
    {
        $pendingEvents  = Event::where('status', 'pending')->with('organizer.user')->latest()->get();
        $approvedEvents = Event::where('status', 'approved')->with('organizer.user')->latest()->get();
        $rejectedEvents = Event::where('status', 'rejected')->with('organizer.user')->latest()->get();

        return view('admin.events', compact('pendingEvents', 'approvedEvents', 'rejectedEvents'));
    }

    public function approve(Event $event)
    {
        $event->update(['status' => 'approved', 'reject_reason' => null]);

        return back()->with('success', "Event \"{$event->title}\" disetujui dan akan tampil di map.");
    }

    public function reject(Request $request, Event $event)
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $event->update([
            'status'        => 'rejected',
            'reject_reason' => $request->reason,
        ]);

        return back()->with('success', "Event \"{$event->title}\" telah ditolak.");
    }
}
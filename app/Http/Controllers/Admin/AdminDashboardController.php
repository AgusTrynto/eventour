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

        return view('admin.dashboard', compact('stats', 'recentPendingEO', 'recentPendingEvents'));
    }
}
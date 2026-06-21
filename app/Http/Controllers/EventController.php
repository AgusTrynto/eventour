<?php

namespace App\Http\Controllers;

use App\Models\Event;

class EventController extends Controller
{
    public function show(Event $event)
    {
        if ($event->status !== 'approved') {
            abort(404);
        }

        $event->load('organizer');

        return view('events.show', compact('event'));
    }
}

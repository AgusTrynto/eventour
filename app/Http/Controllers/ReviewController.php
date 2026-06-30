<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Review;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $reviewableEvents = Event::whereHas('tickets', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('status', 'used');
            })
            ->with(['reviews' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->orderByDesc('start_date')
            ->get();

        return view('reviews.index', compact('reviewableEvents'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $userId = Auth::id();
        $hasWatched = Ticket::where('user_id', $userId)
            ->where('event_id', $validated['event_id'])
            ->where('status', 'used')
            ->exists();

        if (! $hasWatched) {
            return back()->with('error', 'Kamu hanya bisa mengulas event yang tiketnya sudah digunakan.');
        }

        Review::updateOrCreate(
            [
                'user_id' => $userId,
                'event_id' => $validated['event_id'],
            ],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
            ]
        );

        return back()->with('success', 'Ulasan berhasil disimpan.');
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsEO
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || Auth::user()->role !== 'eo') {
            abort(403, 'Halaman ini hanya untuk Event Organizer.');
        }

        return $next($request);
    }
}
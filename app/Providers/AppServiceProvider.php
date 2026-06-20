<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\EventOrganizer;
use App\Models\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // View Composer: inject pending counts ke semua view admin.*
        View::composer('admin.*', function ($view) {
            $view->with('pendingSidebar', [
                'eo'     => EventOrganizer::where('status', 'pending')->count(),
                'events' => Event::where('status', 'pending')->count(),
            ]);
        });
    }
}

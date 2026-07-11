<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\EventOrganizer;
use App\Models\Order;
use App\Models\Payout;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
                'eo' => EventOrganizer::where('status', 'pending')->count(),
                'events' => Event::where('status', 'pending')->count(),
                'payouts' => Payout::whereIn('status', ['pending', 'failed'])->count(),
                'refunds' => Order::whereIn('payment_status', [
                    'refund_manual_pending',
                    'refund_manual_processing',
                    'refund_payout_pending',
                    'refund_payout_failed',
                ])->count(),
            ]);
        });
    }
}

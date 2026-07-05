<?php

namespace Database\Seeders;

use App\Models\RecommendationFeatureSnapshot;
use App\Models\Ticket;
use App\Services\RecommendationFeatureSnapshotService;
use Illuminate\Database\Seeder;

class RecommendationFeatureSnapshotSeeder extends Seeder
{
    public function run(): void
    {
        $snapshotService = app(RecommendationFeatureSnapshotService::class);
        $beforeCount = RecommendationFeatureSnapshot::count();
        $processedCount = 0;

        Ticket::query()
            ->where('status', '!=', 'cancelled')
            ->whereHas('order', function ($query) {
                $query->whereIn('payment_status', ['paid', 'disbursed'])
                    ->whereNull('refunded_at');
            })
            ->with(['event', 'order', 'user'])
            ->chunkById(100, function ($tickets) use ($snapshotService, &$processedCount) {
                foreach ($tickets as $ticket) {
                    if ($snapshotService->recordPurchasedTicket($ticket)) {
                        $processedCount++;
                    }
                }
            });

        $afterCount = RecommendationFeatureSnapshot::count();
        $createdCount = max(0, $afterCount - $beforeCount);

        $this->command?->info(
            "Recommendation feature snapshots synced: {$processedCount} processed, {$createdCount} created."
        );
    }
}

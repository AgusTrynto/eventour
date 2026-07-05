<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\RecommendationFeatureSnapshot;
use App\Services\RecommendationFeatureSnapshotService;
use Illuminate\Database\Seeder;

class RecommendationFeatureSnapshotSeeder extends Seeder
{
    public function run(): void
    {
        $snapshotService = app(RecommendationFeatureSnapshotService::class);
        $beforeCount = RecommendationFeatureSnapshot::count();
        $processedCount = 0;

        Order::query()
            ->whereIn('payment_status', RecommendationFeatureSnapshotService::interestPaymentStatuses())
            ->with(['event', 'user'])
            ->chunkById(100, function ($orders) use ($snapshotService, &$processedCount) {
                foreach ($orders as $order) {
                    $snapshotService->recordPurchasedOrder($order);
                    $processedCount++;
                }
            });

        $afterCount = RecommendationFeatureSnapshot::count();
        $createdCount = max(0, $afterCount - $beforeCount);

        $this->command?->info(
            "Recommendation feature snapshots synced: {$processedCount} processed, {$createdCount} created."
        );
    }
}

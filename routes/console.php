<?php

use App\Models\Event;
use App\Models\RecommendationFeatureSnapshot;
use App\Services\NcbfFeatureVectorService;
use App\Services\RecommendationFeatureSnapshotService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('recommendations:export-ncbf-training {--output=} {--negatives=3}', function () {
    $output = $this->option('output') ?: storage_path('app/recommendation/ncbf_training.json');
    $negativeRatio = max(1, (int) $this->option('negatives'));
    $featureVectors = app(NcbfFeatureVectorService::class);
    $snapshotService = app(RecommendationFeatureSnapshotService::class);

    $snapshots = RecommendationFeatureSnapshot::query()
        ->where('interaction_type', 'purchased')
        ->where('label', 1)
        ->whereHas('order', function ($query) {
            $query->whereIn('payment_status', ['paid', 'disbursed'])
                ->whereNull('refunded_at');
        })
        ->with(['event', 'order', 'user'])
        ->get()
        ->filter(fn (RecommendationFeatureSnapshot $snapshot) => $snapshot->event !== null && $snapshot->user !== null)
        ->values();

    $events = Event::query()
        ->where('status', 'approved')
        ->get();

    if ($snapshots->isEmpty() || $events->isEmpty()) {
        $this->warn('Tidak ada snapshot/event yang cukup untuk training.');

        return 1;
    }

    $maxPrice = max(
        1.0,
        (float) $snapshots->max('event_price'),
        (float) $events->max('price')
    );

    $samples = [];

    foreach ($snapshots->groupBy('user_id') as $userSnapshots) {
        $user = $userSnapshots->first()->user;
        $userVector = $featureVectors->userProfileVector($userSnapshots, $maxPrice);
        $purchasedEventIds = $userSnapshots->pluck('event_id')->unique()->values();

        foreach ($userSnapshots as $snapshot) {
            $eventVector = $featureVectors->snapshotVector($snapshot, $maxPrice);
            $samples[] = [
                'features' => $featureVectors->pairVector($userVector, $eventVector),
                'label' => 1,
                'user_id' => $snapshot->user_id,
                'event_id' => $snapshot->event_id,
            ];
        }

        $negativeLimit = $userSnapshots->count() * $negativeRatio;
        $negativeEvents = $events
            ->reject(fn (Event $event) => $purchasedEventIds->contains($event->id))
            ->values()
            ->take($negativeLimit);

        foreach ($negativeEvents as $event) {
            $eventVector = $featureVectors->eventVector(
                $event,
                $maxPrice,
                $snapshotService->distanceFromUser($user, $event)
            );

            $samples[] = [
                'features' => $featureVectors->pairVector($userVector, $eventVector),
                'label' => 0,
                'user_id' => $user->id,
                'event_id' => $event->id,
            ];
        }
    }

    $directory = dirname($output);

    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }

    file_put_contents($output, json_encode([
        'generated_at' => now()->toISOString(),
        'input_dim' => $featureVectors->pairVectorSize(),
        'event_vector_dim' => $featureVectors->eventVectorSize(),
        'max_price' => $maxPrice,
        'positive_count' => collect($samples)->where('label', 1)->count(),
        'negative_count' => collect($samples)->where('label', 0)->count(),
        'samples' => $samples,
    ], JSON_PRETTY_PRINT));

    $this->info("NCBF training dataset exported to {$output}");
    $this->info('Samples: ' . count($samples));

    return 0;
})->purpose('Export NCBF training samples for the Keras .h5 model');

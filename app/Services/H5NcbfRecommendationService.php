<?php

namespace App\Services;

use App\Models\Event;
use App\Models\RecommendationFeatureSnapshot;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class H5NcbfRecommendationService
{
    public function __construct(
        private readonly NcbfFeatureVectorService $featureVectorService
    ) {
    }

    public function scoreCandidates(
        User $user,
        Collection $historySnapshots,
        Collection $candidates,
        float $maxPrice
    ): ?array {
        if (!$this->isEnabled()) {
            return null;
        }

        $modelPath = (string) config('recommendation.h5.model_path');
        $predictScript = (string) config('recommendation.h5.predict_script');

        if (!is_file($modelPath) || !is_file($predictScript)) {
            return null;
        }

        $historySnapshots = $historySnapshots
            ->filter(fn ($snapshot) => $snapshot instanceof RecommendationFeatureSnapshot && $snapshot->event !== null)
            ->values();

        if ($historySnapshots->isEmpty() || $candidates->isEmpty()) {
            return null;
        }

        $modelMaxPrice = $this->modelMaxPrice($maxPrice);
        $referencePaidAt = $this->latestPaidAt($historySnapshots);
        $userVector = $this->featureVectorService->userProfileVector($historySnapshots, $modelMaxPrice);
        $samples = $candidates
            ->map(function (Event $event) use ($userVector, $modelMaxPrice, $referencePaidAt) {
                $eventVector = $this->featureVectorService->eventVector(
                    $event,
                    $modelMaxPrice,
                    null,
                    null,
                    $referencePaidAt
                );

                return [
                    'event_id' => $event->id,
                    'features' => $this->featureVectorService->pairVector($userVector, $eventVector),
                ];
            })
            ->values()
            ->all();

        $payload = json_encode(['samples' => $samples]);

        if ($payload === false) {
            return null;
        }

        $process = new Process([
            (string) config('recommendation.h5.python'),
            $predictScript,
            '--model',
            $modelPath,
        ], null, [
            'PYTHONHASHSEED' => '0',
            'PYTHONIOENCODING' => 'utf-8',
            'TF_CPP_MIN_LOG_LEVEL' => '2',
        ]);

        $process->setInput($payload);
        $process->setTimeout((int) config('recommendation.h5.timeout', 8));

        try {
            $process->run();
        } catch (\Throwable $exception) {
            Log::warning('NCBF .h5 prediction process failed: ' . $exception->getMessage());

            return null;
        }

        if (!$process->isSuccessful()) {
            Log::warning('NCBF .h5 prediction failed: ' . trim($process->getErrorOutput()));

            return null;
        }

        $decoded = json_decode($process->getOutput(), true);

        if (!is_array($decoded) || !isset($decoded['scores']) || !is_array($decoded['scores'])) {
            return null;
        }

        return collect($decoded['scores'])
            ->filter(fn ($score) => isset($score['event_id'], $score['score']))
            ->mapWithKeys(fn ($score) => [(int) $score['event_id'] => max(0.0, min(1.0, (float) $score['score']))])
            ->all();
    }

    private function isEnabled(): bool
    {
        return (bool) config('recommendation.h5.enabled', true);
    }

    private function modelMaxPrice(float $fallback): float
    {
        $metadataPath = (string) config('recommendation.h5.metadata_path');

        if (!is_file($metadataPath)) {
            return max(1.0, $fallback);
        }

        $metadata = json_decode((string) file_get_contents($metadataPath), true);
        $maxPrice = is_array($metadata) ? ($metadata['max_price'] ?? null) : null;

        if (!is_numeric($maxPrice) || (float) $maxPrice <= 0.0) {
            return max(1.0, $fallback);
        }

        return (float) $maxPrice;
    }

    private function latestPaidAt(Collection $historySnapshots): ?\DateTimeInterface
    {
        return $historySnapshots
            ->map(fn (RecommendationFeatureSnapshot $snapshot) => $snapshot->paid_at ?? $snapshot->order?->paid_at)
            ->filter(fn ($paidAt) => $paidAt instanceof \DateTimeInterface)
            ->sortByDesc(fn (\DateTimeInterface $paidAt) => $paidAt->getTimestamp())
            ->first();
    }
}

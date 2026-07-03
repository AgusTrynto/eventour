<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class ReviewSummaryService
{
    public function summarize(Event $event, Collection $reviews): array
    {
        if ($reviews->isEmpty()) {
            throw new RuntimeException('Belum ada ulasan yang bisa diringkas.');
        }

        $apiKey = config('services.gemini.key');

        if (! $apiKey) {
            throw new RuntimeException('GEMINI_API_KEY belum diatur di file .env.');
        }

        $response = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
            ])
            ->acceptJson()
            ->timeout(45)
            ->post(config('services.gemini.interactions_url'), [
                'model' => config('services.gemini.summary_model'),
                'store' => false,
                'input' => [
                    [
                        'type' => 'user_input',
                        'content' => $this->buildPrompt($event, $reviews),
                    ],
                ],
            ]);

        if ($response->failed()) {
            $message = $response->json('error.message') ?: 'Layanan Gemini sedang tidak bisa membuat kesimpulan.';

            throw new RuntimeException($message);
        }

        $outputText = $this->extractOutputText($response->json());

        if (! $outputText) {
            throw new RuntimeException('Respons AI kosong.');
        }

        return $this->normalizeAnalysis($outputText);
    }

    private function buildPrompt(Event $event, Collection $reviews): string
    {
        $reviewLines = $reviews
            ->take(80)
            ->values()
            ->map(function ($review, int $index) {
                $comment = trim((string) $review->comment);
                $comment = $comment !== '' ? Str::limit($comment, 700) : 'Tidak ada komentar teks.';

                return sprintf(
                    '%d. Rating: %d/5 | Ulasan: %s',
                    $index + 1,
                    $review->rating,
                    $comment
                );
            })
            ->implode("\n");

        return <<<PROMPT
Ringkas ulasan untuk event berikut.

Peran kamu:
- Kamu adalah analis ulasan event untuk tim Event Organizer.
- Buat kesimpulan praktis dalam bahasa Indonesia.
- Jangan mengarang data di luar ulasan.
- Jawaban harus singkat dan langsung bisa dipakai tim analisis EO.

Event: {$event->title}
Lokasi: {$event->location_name}
Jumlah ulasan: {$reviews->count()}
Rating rata-rata: {$reviews->avg('rating')}

Daftar ulasan:
{$reviewLines}

Balas hanya JSON valid tanpa markdown dengan struktur:
{
  "summary": "2-4 kalimat kesimpulan utama",
  "sentiment": "positif|netral|negatif|campuran",
  "positive_points": ["maksimal 4 poin apresiasi peserta"],
  "negative_points": ["maksimal 4 masalah/keluhan peserta"],
  "recommendations": ["maksimal 4 rekomendasi aksi untuk EO"]
}
PROMPT;
    }

    private function extractOutputText(array $payload): string
    {
        if (! empty($payload['output_text'])) {
            return trim((string) $payload['output_text']);
        }

        $texts = [];

        foreach ($payload['steps'] ?? [] as $step) {
            $content = $step['content'] ?? [];

            if (is_string($content)) {
                $texts[] = $content;

                continue;
            }

            foreach ($content as $contentItem) {
                if (is_string($contentItem)) {
                    $texts[] = $contentItem;

                    continue;
                }

                if (isset($contentItem['text'])) {
                    $texts[] = $contentItem['text'];
                }
            }
        }

        foreach ($payload['output'] ?? [] as $outputItem) {
            foreach ($outputItem['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text' && isset($content['text'])) {
                    $texts[] = $content['text'];
                }
            }
        }

        foreach ($payload['candidates'] ?? [] as $candidate) {
            foreach ($candidate['content']['parts'] ?? [] as $part) {
                if (isset($part['text'])) {
                    $texts[] = $part['text'];
                }
            }
        }

        return trim(implode("\n", $texts));
    }

    private function normalizeAnalysis(string $outputText): array
    {
        $json = $this->extractJson($outputText);
        $data = json_decode($json, true);

        if (! is_array($data)) {
            return [
                'summary' => $outputText,
                'sentiment' => 'campuran',
                'positive_points' => [],
                'negative_points' => [],
                'recommendations' => [],
            ];
        }

        return [
            'summary' => trim((string) ($data['summary'] ?? $outputText)),
            'sentiment' => trim((string) ($data['sentiment'] ?? 'campuran')),
            'positive_points' => $this->normalizeList($data['positive_points'] ?? []),
            'negative_points' => $this->normalizeList($data['negative_points'] ?? []),
            'recommendations' => $this->normalizeList($data['recommendations'] ?? []),
        ];
    }

    private function extractJson(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text) ?? $text;

        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false || $end <= $start) {
            return $text;
        }

        return substr($text, $start, $end - $start + 1);
    }

    private function normalizeList(mixed $value): array
    {
        if (is_string($value)) {
            $value = [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn ($item) => is_string($item) && trim($item) !== '')
            ->map(fn ($item) => trim($item))
            ->take(4)
            ->values()
            ->all();
    }
}

<?php

namespace App\Services\Front;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TrustpilotProfileData
{
    public function get(): array
    {
        $config = (array) config('services.trustpilot', []);
        $cacheMinutes = max(5, (int) ($config['cache_minutes'] ?? 60));

        return Cache::remember(
            'front.trustpilot.profile-data',
            now()->addMinutes($cacheMinutes),
            fn (): array => $this->fetchLiveData($config)
        );
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function fetchLiveData(array $config): array
    {
        $fallback = [
            'profile_url' => (string) ($config['profile_url'] ?? ''),
            'business_unit_id' => (string) ($config['business_unit_id'] ?? ''),
            'display_name' => (string) ($config['display_name'] ?? 'Trustpilot'),
            'review_count' => (int) ($config['review_count'] ?? 0),
            'trust_score' => (float) ($config['trust_score'] ?? 0),
            'reviews' => is_array($config['reviews'] ?? null) ? $config['reviews'] : [],
        ];

        $profileUrl = $fallback['profile_url'];
        if ($profileUrl === '') {
            return $fallback;
        }

        try {
            $response = Http::timeout(10)
                ->retry(1, 250)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; MariachisBot/1.0; +https://mariachis.co)',
                    'Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8',
                ])
                ->get($profileUrl);

            if (! $response->successful()) {
                return $fallback;
            }

            $payload = $this->extractNextDataPayload((string) $response->body());
            if (! $payload) {
                return $fallback;
            }

            $pageProps = (array) Arr::get($payload, 'props.pageProps', []);
            $businessUnit = (array) ($pageProps['businessUnit'] ?? []);
            $reviews = collect($pageProps['reviews'] ?? [])
                ->map(function ($review): ?array {
                    if (! is_array($review)) {
                        return null;
                    }

                    $rating = (int) ($review['rating'] ?? 0);
                    if (! in_array($rating, [4, 5], true)) {
                        return null;
                    }

                    $publishedAt = Arr::get($review, 'dates.publishedDate');
                    $publishedDate = $publishedAt ? CarbonImmutable::parse($publishedAt) : null;

                    return [
                        'stars' => $rating,
                        'title' => trim((string) ($review['title'] ?? '')),
                        'excerpt' => trim((string) ($review['text'] ?? '')),
                        'author' => trim((string) Arr::get($review, 'consumer.displayName', '')),
                        'published_at' => $publishedDate?->toIso8601String(),
                        'published_label' => $publishedDate?->diffForHumans(),
                    ];
                })
                ->filter()
                ->sortByDesc('published_at')
                ->take(3)
                ->values()
                ->all();

            return [
                'profile_url' => $fallback['profile_url'],
                'business_unit_id' => (string) ($businessUnit['id'] ?? $fallback['business_unit_id']),
                'display_name' => (string) ($businessUnit['displayName'] ?? $fallback['display_name']),
                'review_count' => (int) ($businessUnit['numberOfReviews'] ?? $fallback['review_count']),
                'trust_score' => (float) ($businessUnit['trustScore'] ?? $fallback['trust_score']),
                'reviews' => $reviews,
            ];
        } catch (\Throwable) {
            return $fallback;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractNextDataPayload(string $html): ?array
    {
        if (! preg_match('#<script id="__NEXT_DATA__" type="application/json">(.*?)</script>#s', $html, $matches)) {
            return null;
        }

        $decoded = json_decode($matches[1], true);

        return is_array($decoded) ? $decoded : null;
    }
}

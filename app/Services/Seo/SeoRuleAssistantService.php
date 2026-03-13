<?php

namespace App\Services\Seo;

use App\Support\PortalHosts;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SeoRuleAssistantService
{
    public function __construct(private readonly SeoPageCatalog $catalog)
    {
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function suggestCanonical(string $type, array $context = []): ?string
    {
        $explicit = $this->cleanAbsoluteUrl(
            (string) ($context['canonical_override'] ?? $context['canonical'] ?? '')
        );

        if ($explicit) {
            return $explicit;
        }

        return match ($type) {
            'page' => $this->pageCanonical($context),
            'post' => $this->slugCanonical('/blog', $context['slug'] ?? null),
            'listing' => $this->slugCanonical('/mariachi', $context['slug'] ?? null),
            'profile' => $this->profileCanonical($context['handle'] ?? $context['slug'] ?? null),
            'landing_template' => $this->landingCanonical($context),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function generateJsonLd(string $type, array $context = []): ?string
    {
        $payload = match ($type) {
            'page' => $this->pageSchema($context),
            'post' => $this->articleSchema($context),
            'listing' => $this->listingSchema($context),
            'profile' => $this->profileSchema($context),
            'landing_template' => $this->landingSchema($context),
            'faq' => $this->faqSchema($context),
            default => null,
        };

        if ($payload === null) {
            return null;
        }

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: null;
    }

    public function isValidJson(?string $json): bool
    {
        $normalized = trim((string) $json);

        if ($normalized === '') {
            return true;
        }

        json_decode($normalized, true);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function pageCanonical(array $context): ?string
    {
        $pageKey = (string) ($context['page_key'] ?? '');
        $definition = $pageKey !== '' ? $this->catalog->definition($pageKey) : null;
        $path = (string) ($context['path'] ?? $definition['path'] ?? '');

        return $path !== '' ? $this->publicUrl($path) : null;
    }

    /**
     * @param  mixed  $slug
     */
    private function slugCanonical(string $prefix, mixed $slug): ?string
    {
        $normalizedSlug = trim((string) $slug);

        return $normalizedSlug !== ''
            ? $this->publicUrl(trim($prefix, '/').'/'.$normalizedSlug)
            : null;
    }

    /**
     * @param  mixed  $handle
     */
    private function profileCanonical(mixed $handle): ?string
    {
        $normalizedHandle = trim((string) $handle);

        return $normalizedHandle !== ''
            ? $this->publicUrl('@'.$normalizedHandle)
            : null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function landingCanonical(array $context): ?string
    {
        $path = trim((string) ($context['path'] ?? $context['canonical_path'] ?? ''));
        if ($path !== '') {
            return $this->publicUrl($path);
        }

        $slug = trim((string) ($context['slug'] ?? ''));
        if ($slug !== '') {
            return $this->publicUrl($slug);
        }

        $citySlug = trim((string) ($context['city_slug'] ?? ''));
        $scopeSlug = trim((string) ($context['scope_slug'] ?? ''));

        if ($citySlug !== '' && $scopeSlug !== '') {
            return $this->publicUrl($citySlug.'/'.$scopeSlug);
        }

        return $citySlug !== '' ? $this->publicUrl($citySlug) : null;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>|array<int, array<string, mixed>>|null
     */
    private function pageSchema(array $context): array|null
    {
        $pageKey = (string) ($context['page_key'] ?? '');
        $canonical = $this->suggestCanonical('page', $context);

        if ($pageKey === '404') {
            return null;
        }

        if ($pageKey === 'home') {
            return [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => $this->contextText($context, 'title') ?: 'Mariachis.co',
                'description' => $this->contextText($context, 'description'),
                'url' => $canonical ?: $this->publicUrl('/'),
            ];
        }

        if ($pageKey === 'help' && ! empty($context['faq_items'])) {
            return [
                $this->webPageSchema($context, $canonical),
                $this->faqSchema($context),
            ];
        }

        if ($pageKey === 'blog_index') {
            return [
                $this->webPageSchema($context, $canonical, 'CollectionPage'),
                $this->breadcrumbSchema($canonical, [
                    ['name' => 'Inicio', 'url' => $this->publicUrl('/')],
                    ['name' => 'Blog', 'url' => $canonical],
                ]),
            ];
        }

        return $this->webPageSchema($context, $canonical);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function articleSchema(array $context): array
    {
        $canonical = $this->suggestCanonical('post', $context);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $this->contextText($context, 'title'),
            'description' => $this->contextText($context, 'description'),
            'url' => $canonical,
            'mainEntityOfPage' => $canonical,
        ];

        if ($image = $this->contextText($context, 'image')) {
            $schema['image'] = $image;
        }

        if ($publishedAt = $this->contextText($context, 'published_at')) {
            $schema['datePublished'] = $publishedAt;
        }

        if ($updatedAt = $this->contextText($context, 'updated_at')) {
            $schema['dateModified'] = $updatedAt;
        }

        $about = $this->normalizeList($context['about'] ?? []);
        if ($about !== []) {
            $schema['about'] = $about;
        }

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function listingSchema(array $context): array
    {
        $canonical = $this->suggestCanonical('listing', $context);
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $this->contextText($context, 'name') ?: $this->contextText($context, 'title'),
            'description' => $this->contextText($context, 'description'),
            'url' => $canonical,
        ];

        if ($phone = $this->contextText($context, 'telephone')) {
            $schema['telephone'] = $phone;
        }

        if ($image = $this->contextText($context, 'image')) {
            $schema['image'] = $image;
        }

        $address = array_filter([
            '@type' => 'PostalAddress',
            'addressLocality' => $this->contextText($context, 'city_name'),
            'addressRegion' => $this->contextText($context, 'state'),
            'addressCountry' => $this->contextText($context, 'country'),
            'postalCode' => $this->contextText($context, 'postal_code'),
            'streetAddress' => $this->contextText($context, 'address'),
        ]);

        if (count($address) > 1) {
            $schema['address'] = $address;
        }

        $areas = $this->normalizeList($context['area_served'] ?? []);
        if ($areas !== []) {
            $schema['areaServed'] = $areas;
        }

        if (filled($context['rating_value'] ?? null) && filled($context['review_count'] ?? null)) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (float) $context['rating_value'],
                'reviewCount' => (int) $context['review_count'],
            ];
        }

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function profileSchema(array $context): array
    {
        $canonical = $this->suggestCanonical('profile', $context);
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'MusicGroup',
            'name' => $this->contextText($context, 'name') ?: $this->contextText($context, 'title'),
            'description' => $this->contextText($context, 'description'),
            'url' => $canonical,
        ];

        if ($image = $this->contextText($context, 'image')) {
            $schema['image'] = $image;
        }

        $areas = $this->normalizeList($context['area_served'] ?? []);
        if ($areas !== []) {
            $schema['areaServed'] = $areas;
        }

        $sameAs = $this->normalizeList($context['same_as'] ?? []);
        if ($sameAs !== []) {
            $schema['sameAs'] = $sameAs;
        }

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, array<string, mixed>>
     */
    private function landingSchema(array $context): array
    {
        $canonical = $this->suggestCanonical('landing_template', $context);
        $title = $this->contextText($context, 'title');
        $description = $this->contextText($context, 'description');

        return [
            [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => $title,
                'description' => $description,
                'url' => $canonical,
            ],
            $this->breadcrumbSchema($canonical, $this->landingBreadcrumbs($context, $canonical)),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>|null
     */
    private function faqSchema(array $context): ?array
    {
        $faqItems = collect($context['faq_items'] ?? [])
            ->map(function (mixed $item): ?array {
                $question = $this->contextText(is_array($item) ? $item : [], 'question');
                $answer = $this->contextText(is_array($item) ? $item : [], 'answer');

                if (! $question || ! $answer) {
                    return null;
                }

                return [
                    '@type' => 'Question',
                    'name' => $question,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $answer,
                    ],
                ];
            })
            ->filter()
            ->values()
            ->all();

        if ($faqItems === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqItems,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function webPageSchema(array $context, ?string $canonical, string $type = 'WebPage'): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => $this->contextText($context, 'title'),
            'description' => $this->contextText($context, 'description'),
            'url' => $canonical,
        ];
    }

    /**
     * @param  list<array{name:string,url:?string}>  $items
     * @return array<string, mixed>
     */
    private function breadcrumbSchema(?string $canonical, array $items): array
    {
        $elements = collect($items)
            ->filter(fn (array $item): bool => filled($item['name']))
            ->values()
            ->map(function (array $item, int $index): array {
                return [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'item' => $item['url'],
                ];
            })
            ->all();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
            'url' => $canonical,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<array{name:string,url:?string}>
     */
    private function landingBreadcrumbs(array $context, ?string $canonical): array
    {
        $items = [
            ['name' => 'Inicio', 'url' => $this->publicUrl('/')],
        ];

        $country = $this->contextText($context, 'country_name');
        if ($country) {
            $items[] = ['name' => $country, 'url' => $this->publicUrl(Str::slug($country))];
        }

        $city = $this->contextText($context, 'city_name');
        if ($city) {
            $items[] = ['name' => $city, 'url' => $this->publicUrl(Str::slug($city))];
        }

        $scope = $this->contextText($context, 'zone_name') ?: $this->contextText($context, 'event_name');
        if ($scope) {
            $items[] = ['name' => $scope, 'url' => $canonical];
        } elseif ($title = $this->contextText($context, 'title')) {
            $items[] = ['name' => $title, 'url' => $canonical];
        }

        return $items;
    }

    /**
     * @param  mixed  $value
     */
    private function cleanAbsoluteUrl(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $parsed = parse_url($trimmed);
        if (! is_array($parsed) || empty($parsed['host'])) {
            return null;
        }

        $scheme = $parsed['scheme'] ?? PortalHosts::scheme();
        $path = $parsed['path'] ?? '/';
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';

        return rtrim($scheme.'://'.$parsed['host'].$port, '/').($path !== '' ? $path : '/');
    }

    private function publicUrl(string $path = '/'): string
    {
        return PortalHosts::absoluteUrl(PortalHosts::root(), $path);
    }

    /**
     * @param  mixed  $value
     * @return list<string>
     */
    private function normalizeList(mixed $value): array
    {
        if ($value instanceof Collection) {
            $value = $value->all();
        }

        $items = is_array($value) ? $value : [$value];

        return collect($items)
            ->map(function (mixed $item): string {
                if (is_array($item)) {
                    $item = Arr::first($item, fn (mixed $subItem): bool => is_string($subItem) && trim($subItem) !== '');
                }

                return $this->contextText(['value' => $item], 'value') ?: '';
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function contextText(array $context, string $key): ?string
    {
        $value = Arr::get($context, $key);

        if ($value instanceof Collection) {
            $value = $value->all();
        }

        if (is_array($value)) {
            $value = collect($value)
                ->map(fn (mixed $item): string => trim((string) $item))
                ->filter()
                ->implode(', ');
        }

        $normalized = trim(strip_tags((string) $value));

        return $normalized !== '' ? preg_replace('/\s+/u', ' ', $normalized) : null;
    }
}

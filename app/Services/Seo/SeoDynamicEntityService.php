<?php

namespace App\Services\Seo;

use App\Models\EventType;
use App\Models\MarketplaceCity;
use App\Models\MarketplaceZone;
use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\SeoEntityOverride;
use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class SeoDynamicEntityService
{
    public function __construct(
        private readonly SeoSettingsService $settings,
        private readonly SeoTemplateCatalog $templates,
        private readonly SeoTemplateRenderer $renderer,
        private readonly SeoRuleAssistantService $seoRules
    ) {
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>|null
     */
    public function resolve(string $pageType, array $context, Request $request): ?array
    {
        $entityType = $this->normalizeEntityType((string) ($context['entity_type'] ?? ''));
        if ($entityType === '') {
            return null;
        }

        $templateKey = $this->templateKeyForEntityType($entityType);
        $template = $templateKey ? $this->templates->template($templateKey) : null;
        $override = $this->override($entityType, $this->entityId($context));
        $placeholders = $this->placeholderValues($entityType, $context);

        $title = trim((string) ($override?->meta_title
            ?? ($template ? $this->renderer->render($template->title_template, $placeholders) : '')
            ?? ''));
        $description = trim((string) ($override?->meta_description
            ?? ($template ? $this->renderer->render($template->description_template, $placeholders) : '')
            ?? ''));
        $canonical = trim((string) ($override?->canonical_override
            ?: $this->suggestCanonical($entityType, $pageType, $context, $request)));
        $robots = trim((string) ($override?->robots ?? $template?->robots ?? ''));
        $ogImage = $override?->og_image_path ?: $template?->og_image_path;
        $jsonLd = trim((string) ($override?->jsonld_override ?? ''));

        return array_filter([
            'title' => $title !== '' ? $title : null,
            'description' => $description !== '' ? $description : null,
            'canonical' => $canonical !== '' ? $canonical : null,
            'robots' => $robots !== '' ? $robots : null,
            'og_image' => $ogImage ?: null,
            'jsonld' => $jsonLd !== '' ? $jsonLd : null,
            'keywords_target' => $override?->keywords_target ?: $template?->keywords_target,
            'placeholder_values' => $placeholders,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public function entityMeta(string $entityType): array
    {
        $meta = $this->entityDefinitions()[$this->normalizeEntityType($entityType)] ?? null;

        if (! $meta) {
            throw new RuntimeException('Tipo de entidad SEO no soportado.');
        }

        return $meta;
    }

    public function supportsBatch(string $entityType): bool
    {
        return (bool) ($this->entityMeta($entityType)['batch_enabled'] ?? false);
    }

    /**
     * @return list<string>
     */
    public function supportedBatchTypes(): array
    {
        return collect($this->entityDefinitions())
            ->filter(fn (array $meta): bool => (bool) ($meta['batch_enabled'] ?? false))
            ->keys()
            ->values()
            ->all();
    }

    public function findEntity(string $entityType, int $entityId): Model
    {
        $meta = $this->entityMeta($entityType);
        $modelClass = $meta['model'];

        return $modelClass::query()
            ->with($meta['with'] ?? [])
            ->findOrFail($entityId);
    }

    public function override(string $entityType, ?int $entityId): ?SeoEntityOverride
    {
        if (! $entityId) {
            return null;
        }

        return SeoEntityOverride::query()
            ->where('entity_type', $this->normalizeEntityType($entityType))
            ->where('entity_id', $entityId)
            ->first();
    }

    public function publicUrl(string $entityType, Model $entity): ?string
    {
        $entityType = $this->normalizeEntityType($entityType);

        return match ($entityType) {
            'city' => route('seo.landing.slug', ['slug' => (string) $entity->slug]),
            'zone' => $this->zonePublicUrl($entity),
            'event_type' => route('seo.landing.slug', ['slug' => (string) ($entity->slug ?: Str::slug((string) $entity->name))]),
            'listing' => route('mariachi.public.show', ['slug' => (string) $entity->slug]),
            'profile' => route('mariachi.provider.public.show', ['handle' => (string) $entity->slug]),
            'service_type' => null,
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function buildContextForEntity(string $entityType, Model $entity): array
    {
        $entityType = $this->normalizeEntityType($entityType);

        return match ($entityType) {
            'city' => $this->cityContext($entity),
            'zone' => $this->zoneContext($entity),
            'event_type' => $this->eventTypeContext($entity),
            'listing' => $this->listingContext($entity),
            'profile' => $this->profileContext($entity),
            'service_type' => $this->serviceTypeContext($entity),
            default => throw new RuntimeException('Tipo de entidad SEO no soportado.'),
        };
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, scalar|null>
     */
    public function placeholderValues(string $entityType, array $context): array
    {
        $country = trim((string) ($context['country'] ?? config('seo.default_country_name', 'Colombia')));
        $siteName = $this->settings->siteName();

        $values = [
            'site_name' => $siteName,
            'country' => $country !== '' ? $country : 'Colombia',
            'city' => $this->stringValue($context['city'] ?? $context['city_name'] ?? null),
            'city_slug' => $this->stringValue($context['city_slug'] ?? null),
            'zone' => $this->stringValue($context['zone'] ?? $context['zone_name'] ?? null),
            'event' => $this->stringValue($context['event'] ?? $context['event_name'] ?? null),
            'service' => $this->stringValue($context['service'] ?? $context['service_name'] ?? null),
            'listing_title' => $this->stringValue($context['listing_title'] ?? $context['title'] ?? null),
            'price_from' => $this->stringValue($context['price_from'] ?? null),
            'provider_name' => $this->stringValue($context['provider_name'] ?? $context['name'] ?? null),
            'provider_city' => $this->stringValue($context['provider_city'] ?? $context['city'] ?? $context['city_name'] ?? null),
            'active_listings_count' => $this->stringValue($context['active_listings_count'] ?? null),
            'listing_count' => $this->stringValue($context['listing_count'] ?? null),
            'min_price' => $this->stringValue($context['min_price'] ?? null),
            'max_price' => $this->stringValue($context['max_price'] ?? null),
        ];

        return $values;
    }

    /**
     * @return array<string, array{
     *   label:string,
     *   model:class-string<Model>,
     *   template_key:string,
     *   batch_enabled:bool,
     *   with:list<string>
     * }>
     */
    private function entityDefinitions(): array
    {
        return [
            'city' => [
                'label' => 'Ciudad',
                'model' => MarketplaceCity::class,
                'template_key' => 'city',
                'batch_enabled' => true,
                'with' => [],
            ],
            'zone' => [
                'label' => 'Zona',
                'model' => MarketplaceZone::class,
                'template_key' => 'zone',
                'batch_enabled' => false,
                'with' => ['city:id,name,slug'],
            ],
            'event_type' => [
                'label' => 'Tipo de evento',
                'model' => EventType::class,
                'template_key' => 'event_type',
                'batch_enabled' => true,
                'with' => [],
            ],
            'listing' => [
                'label' => 'Anuncio',
                'model' => MariachiListing::class,
                'template_key' => 'listing',
                'batch_enabled' => false,
                'with' => [
                    'mariachiProfile.user:id,name,first_name,last_name',
                    'serviceAreas:id,mariachi_listing_id,city_name',
                    'photos:id,mariachi_listing_id,path,is_featured,sort_order',
                ],
            ],
            'profile' => [
                'label' => 'Perfil',
                'model' => MariachiProfile::class,
                'template_key' => 'profile',
                'batch_enabled' => false,
                'with' => [
                    'user:id,name,first_name,last_name',
                ],
            ],
            'service_type' => [
                'label' => 'Tipo de servicio',
                'model' => ServiceType::class,
                'template_key' => 'service_type',
                'batch_enabled' => true,
                'with' => [],
            ],
        ];
    }

    private function templateKeyForEntityType(string $entityType): ?string
    {
        return $this->entityDefinitions()[$entityType]['template_key'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function entityId(array $context): ?int
    {
        $entityId = $context['entity_id'] ?? null;

        return is_numeric($entityId) && (int) $entityId > 0 ? (int) $entityId : null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function suggestCanonical(string $entityType, string $pageType, array $context, Request $request): ?string
    {
        $type = match ($entityType) {
            'listing' => 'listing',
            'profile' => 'profile',
            default => 'landing_template',
        };

        return $this->seoRules->suggestCanonical($type, array_merge($context, [
            'current_url' => $request->url(),
            'canonical' => $context['canonical'] ?? $request->url(),
            'path' => $context['canonical_path'] ?? parse_url($request->url(), PHP_URL_PATH),
        ]));
    }

    private function normalizeEntityType(string $entityType): string
    {
        return match ($entityType) {
            'event' => 'event_type',
            'service' => 'service_type',
            default => trim($entityType),
        };
    }

    private function cityContext(Model $entity): array
    {
        /** @var MarketplaceCity $entity */
        $query = $entity->listings()->published();

        return [
            'entity_type' => 'city',
            'entity_id' => (int) $entity->id,
            'city' => $entity->name,
            'city_slug' => $entity->slug,
            'country' => 'Colombia',
            'listing_count' => (string) $query->count(),
            'min_price' => $this->formatPrice((clone $query)->min('base_price')),
            'max_price' => $this->formatPrice((clone $query)->max('base_price')),
            'canonical' => $this->publicUrl('city', $entity),
            'canonical_path' => '/mariachis/'.$entity->slug,
        ];
    }

    private function zoneContext(Model $entity): array
    {
        /** @var MarketplaceZone $entity */
        $entity->loadMissing('city:id,name,slug');

        $listingIds = $entity->serviceAreas()
            ->whereHas('listing', fn (Builder $query): Builder => $query->published())
            ->distinct()
            ->pluck('mariachi_listing_id');

        $priceQuery = MariachiListing::query()
            ->published()
            ->whereIn('id', $listingIds);

        return [
            'entity_type' => 'zone',
            'entity_id' => (int) $entity->id,
            'zone' => $entity->name,
            'city' => $entity->city?->name ?: config('seo.default_country_name', 'Colombia'),
            'country' => 'Colombia',
            'listing_count' => (string) $listingIds->count(),
            'min_price' => $this->formatPrice((clone $priceQuery)->min('base_price')),
            'canonical' => $this->publicUrl('zone', $entity),
            'canonical_path' => $entity->city?->slug ? '/mariachis/'.$entity->city->slug.'/'.$entity->slug : null,
        ];
    }

    private function eventTypeContext(Model $entity): array
    {
        /** @var EventType $entity */
        $query = $entity->mariachiListings()->published();

        return [
            'entity_type' => 'event_type',
            'entity_id' => (int) $entity->id,
            'event' => mb_strtolower($entity->name),
            'city' => config('seo.default_country_name', 'Colombia'),
            'country' => 'Colombia',
            'listing_count' => (string) $query->count(),
            'canonical' => $this->publicUrl('event_type', $entity),
            'canonical_path' => '/mariachis/'.($entity->slug ?: Str::slug($entity->name)),
        ];
    }

    private function listingContext(Model $entity): array
    {
        /** @var MariachiListing $entity */
        $providerName = $entity->business_name ?: $entity->mariachiProfile?->business_name ?: $entity->mariachiProfile?->user?->display_name ?: 'Mariachi';
        $zone = $entity->serviceAreas->pluck('city_name')->filter()->first();
        $featuredPhoto = $entity->photos->firstWhere('is_featured', true) ?? $entity->photos->first();

        return [
            'entity_type' => 'listing',
            'entity_id' => (int) $entity->id,
            'listing_title' => $entity->title,
            'title' => $entity->title,
            'provider_name' => $providerName,
            'city' => $entity->city_name ?: 'Colombia',
            'city_name' => $entity->city_name ?: 'Colombia',
            'zone' => $zone,
            'zone_name' => $zone,
            'country' => $entity->country ?: 'Colombia',
            'price_from' => $this->formatPrice($entity->base_price),
            'canonical' => $this->publicUrl('listing', $entity),
            'canonical_path' => '/mariachi/'.$entity->slug,
            'name' => $providerName,
            'description' => $entity->short_description ?: $entity->description,
            'image' => $featuredPhoto?->path ? asset('storage/'.$featuredPhoto->path) : null,
            'telephone' => $entity->mariachiProfile?->whatsapp ?: $entity->mariachiProfile?->user?->phone,
            'area_served' => $entity->serviceAreas->pluck('city_name')->filter()->values()->all(),
            'address' => $entity->address,
            'postal_code' => $entity->postal_code,
            'state' => $entity->state,
            'rating_value' => round((float) ($entity->reviews()->publicVisible()->avg('rating') ?? 0), 1),
            'review_count' => $entity->reviews()->publicVisible()->count(),
        ];
    }

    private function profileContext(Model $entity): array
    {
        /** @var MariachiProfile $entity */
        $providerName = $entity->business_name ?: $entity->user?->display_name ?: 'Mariachi';
        $activeListingsCount = $entity->activeListings()->count();

        return [
            'entity_type' => 'profile',
            'entity_id' => (int) $entity->id,
            'provider_name' => $providerName,
            'provider_city' => $entity->city_name ?: 'Colombia',
            'country' => $entity->country ?: 'Colombia',
            'active_listings_count' => (string) $activeListingsCount,
            'canonical' => $this->publicUrl('profile', $entity),
            'canonical_path' => '/@'.$entity->slug,
            'name' => $providerName,
            'description' => $entity->short_description ?: $entity->full_description,
            'image' => $entity->logo_path ? asset('storage/'.$entity->logo_path) : null,
            'city_name' => $entity->city_name ?: 'Colombia',
            'area_served' => array_values(array_filter([
                $entity->city_name,
                $entity->state,
                $entity->country ?: 'Colombia',
            ])),
            'same_as' => array_values(array_filter([
                $entity->website,
                $entity->instagram,
                $entity->facebook,
                $entity->tiktok,
                $entity->youtube,
            ])),
        ];
    }

    private function serviceTypeContext(Model $entity): array
    {
        /** @var ServiceType $entity */
        $query = $entity->mariachiListings()->published();

        return [
            'entity_type' => 'service_type',
            'entity_id' => (int) $entity->id,
            'service' => mb_strtolower($entity->name),
            'country' => 'Colombia',
            'listing_count' => (string) $query->count(),
        ];
    }

    private function stringValue(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = collect($value)
                ->map(fn (mixed $item): string => trim((string) $item))
                ->filter()
                ->implode(', ');
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function zonePublicUrl(Model $entity): ?string
    {
        if (! $entity instanceof MarketplaceZone) {
            return null;
        }

        $entity->loadMissing('city:id,name,slug');

        return filled($entity->city?->slug)
            ? route('seo.landing.city-category', ['citySlug' => $entity->city->slug, 'scopeSlug' => (string) $entity->slug])
            : null;
    }

    private function formatPrice(mixed $value): string
    {
        if (! is_numeric($value) || (float) $value <= 0) {
            return 'cotización directa';
        }

        return '$'.number_format((float) $value, 0, ',', '.').' COP';
    }
}

<?php

namespace App\Services\Seo;

use App\Models\SeoTemplate;
use Illuminate\Support\Collection;

class SeoTemplateCatalog
{
    /**
     * @return array<string, array{
     *   label:string,
     *   description:string,
     *   entity_type:string,
     *   batch_enabled:bool,
     *   title_template:string,
     *   description_template:string,
     *   robots:string,
     *   keywords_target:?string,
     *   placeholders:list<string>,
     *   placeholder_samples:array<string, string>
     * }>
     */
    public function definitions(): array
    {
        return [
            'city' => [
                'label' => 'Ciudades',
                'description' => 'Landings de ciudad bajo /mariachis/{slug}.',
                'entity_type' => 'city',
                'batch_enabled' => true,
                'title_template' => 'Mariachis en {{city}} | {{site_name}}',
                'description_template' => 'Encuentra {{listing_count}} opciones de mariachis en {{city}}, {{country}}. Compara perfiles, cobertura y contacto directo en {{site_name}}.',
                'robots' => 'index,follow',
                'keywords_target' => 'mariachis en {{city}}, contratar mariachi {{city}}, serenatas {{city}}',
                'placeholders' => ['site_name', 'country', 'city', 'city_slug', 'listing_count', 'min_price', 'max_price'],
                'placeholder_samples' => [
                    'site_name' => 'Mariachis.co',
                    'country' => 'Colombia',
                    'city' => 'Bogota',
                    'city_slug' => 'bogota',
                    'listing_count' => '24',
                    'min_price' => '$350.000 COP',
                    'max_price' => '$1.200.000 COP',
                ],
            ],
            'zone' => [
                'label' => 'Zonas y barrios',
                'description' => 'Landings ciudad/zona bajo /mariachis/{citySlug}/{scopeSlug}.',
                'entity_type' => 'zone',
                'batch_enabled' => false,
                'title_template' => 'Mariachis en {{zone}}, {{city}} | {{site_name}}',
                'description_template' => 'Descubre {{listing_count}} anuncios de mariachis que cubren {{zone}}, {{city}}. Revisa disponibilidad, precios desde {{min_price}} y contacto directo.',
                'robots' => 'index,follow',
                'keywords_target' => 'mariachis {{zone}}, serenatas {{zone}}, mariachis {{city}}',
                'placeholders' => ['site_name', 'country', 'zone', 'city', 'listing_count', 'min_price'],
                'placeholder_samples' => [
                    'site_name' => 'Mariachis.co',
                    'country' => 'Colombia',
                    'zone' => 'Chapinero',
                    'city' => 'Bogota',
                    'listing_count' => '12',
                    'min_price' => '$350.000 COP',
                ],
            ],
            'event_type' => [
                'label' => 'Tipos de evento',
                'description' => 'Landings de evento y ciudad/evento.',
                'entity_type' => 'event_type',
                'batch_enabled' => true,
                'title_template' => 'Mariachis para {{event}} en {{city}} | {{site_name}}',
                'description_template' => 'Encuentra mariachis para {{event}} en {{city}} con {{listing_count}} anuncios activos, cobertura real y contacto directo en {{site_name}}.',
                'robots' => 'index,follow',
                'keywords_target' => 'mariachis para {{event}}, serenatas {{event}}, mariachis {{city}}',
                'placeholders' => ['site_name', 'country', 'event', 'city', 'listing_count'],
                'placeholder_samples' => [
                    'site_name' => 'Mariachis.co',
                    'country' => 'Colombia',
                    'event' => 'bodas',
                    'city' => 'Bogota',
                    'listing_count' => '18',
                ],
            ],
            'listing' => [
                'label' => 'Anuncios',
                'description' => 'Páginas públicas de anuncio individual.',
                'entity_type' => 'listing',
                'batch_enabled' => false,
                'title_template' => '{{listing_title}} en {{city}} | {{site_name}}',
                'description_template' => 'Conoce {{listing_title}} en {{city}}. Precio desde {{price_from}}, fotos, cobertura y contacto directo con {{provider_name}}.',
                'robots' => 'index,follow',
                'keywords_target' => '{{listing_title}}, mariachis {{city}}, {{provider_name}}',
                'placeholders' => ['site_name', 'country', 'listing_title', 'city', 'zone', 'price_from', 'provider_name'],
                'placeholder_samples' => [
                    'site_name' => 'Mariachis.co',
                    'country' => 'Colombia',
                    'listing_title' => 'Serenatas Mariachi Vargas',
                    'city' => 'Bogota',
                    'zone' => 'Chapinero',
                    'price_from' => '$450.000 COP',
                    'provider_name' => 'Mariachi Vargas',
                ],
            ],
            'profile' => [
                'label' => 'Perfiles',
                'description' => 'Páginas públicas del perfil oficial de un mariachi.',
                'entity_type' => 'profile',
                'batch_enabled' => false,
                'title_template' => '{{provider_name}} en {{provider_city}} | {{site_name}}',
                'description_template' => 'Perfil oficial de {{provider_name}} en {{provider_city}} con {{active_listings_count}} anuncio(s) activo(s), cobertura local y contacto directo.',
                'robots' => 'index,follow',
                'keywords_target' => '{{provider_name}}, mariachi {{provider_city}}, perfil de mariachi',
                'placeholders' => ['site_name', 'country', 'provider_name', 'provider_city', 'active_listings_count'],
                'placeholder_samples' => [
                    'site_name' => 'Mariachis.co',
                    'country' => 'Colombia',
                    'provider_name' => 'Mariachi Vargas',
                    'provider_city' => 'Bogota',
                    'active_listings_count' => '3',
                ],
            ],
            'service_type' => [
                'label' => 'Tipos de servicio',
                'description' => 'Reservado para futuras páginas /servicios/{slug}.',
                'entity_type' => 'service_type',
                'batch_enabled' => true,
                'title_template' => '{{service}} | {{site_name}}',
                'description_template' => 'Explora opciones de {{service}} en {{country}} con perfiles reales, filtros locales y contacto directo en {{site_name}}.',
                'robots' => 'index,follow',
                'keywords_target' => '{{service}}, servicios de mariachi, mariachis {{country}}',
                'placeholders' => ['site_name', 'country', 'service', 'listing_count'],
                'placeholder_samples' => [
                    'site_name' => 'Mariachis.co',
                    'country' => 'Colombia',
                    'service' => 'serenatas sorpresa',
                    'listing_count' => '20',
                ],
            ],
        ];
    }

    public function syncDefaults(): void
    {
        foreach ($this->definitions() as $templateKey => $definition) {
            SeoTemplate::query()->firstOrCreate(
                ['template_key' => $templateKey],
                [
                    'title_template' => $definition['title_template'],
                    'description_template' => $definition['description_template'],
                    'robots' => $definition['robots'],
                    'keywords_target' => $definition['keywords_target'],
                ]
            );
        }
    }

    /**
     * @return Collection<int, SeoTemplate>
     */
    public function templates(): Collection
    {
        $this->syncDefaults();

        return SeoTemplate::query()->orderBy('template_key')->get();
    }

    /**
     * @return array{
     *   label:string,
     *   description:string,
     *   entity_type:string,
     *   batch_enabled:bool,
     *   title_template:string,
     *   description_template:string,
     *   robots:string,
     *   keywords_target:?string,
     *   placeholders:list<string>,
     *   placeholder_samples:array<string, string>
     * }|null
     */
    public function definition(string $templateKey): ?array
    {
        return $this->definitions()[$templateKey] ?? null;
    }

    public function template(string $templateKey): ?SeoTemplate
    {
        $this->syncDefaults();

        return SeoTemplate::query()->where('template_key', $templateKey)->first();
    }
}

<?php

namespace App\Support\Entitlements;

class EntitlementKey
{
    public const MAX_LISTINGS_TOTAL = 'max_listings_total';
    public const LISTING_TERM_PRIMARY_MONTHS = 'listing_term_primary_months';
    public const LISTING_TERM_PRIMARY_DISCOUNT_PERCENT = 'listing_term_primary_discount_percent';
    public const LISTING_TERM_SECONDARY_MONTHS = 'listing_term_secondary_months';
    public const LISTING_TERM_SECONDARY_DISCOUNT_PERCENT = 'listing_term_secondary_discount_percent';
    public const LISTING_TERM_TERTIARY_MONTHS = 'listing_term_tertiary_months';
    public const LISTING_TERM_TERTIARY_DISCOUNT_PERCENT = 'listing_term_tertiary_discount_percent';
    public const MAX_PHOTOS_PER_LISTING = 'max_photos_per_listing';
    public const CAN_ADD_VIDEO = 'can_add_video';
    public const MAX_VIDEOS_PER_LISTING = 'max_videos_per_listing';
    public const CAN_SHOW_WHATSAPP = 'can_show_whatsapp';
    public const CAN_SHOW_PHONE = 'can_show_phone';
    public const MAX_CITIES_COVERED = 'max_cities_covered';
    public const MAX_ZONES_COVERED = 'max_zones_covered';
    public const MAX_EVENT_TYPES = 'max_event_types';
    public const MAX_SERVICE_TYPES = 'max_service_types';
    public const MAX_GROUP_SIZES = 'max_group_sizes';
    public const MAX_BUDGET_RANGES = 'max_budget_ranges';
    public const PRIORITY_LEVEL = 'priority_level';
    public const CAN_FEATURED_CITY = 'can_featured_city';
    public const CAN_FEATURED_HOME = 'can_featured_home';
    public const CAN_REQUEST_VERIFICATION = 'can_request_verification';
    public const HAS_PREMIUM_BADGE = 'has_premium_badge';
    public const HAS_ADVANCED_STATS = 'has_advanced_stats';

    /**
     * @return array<string, array{type:string,category:string,label:string,description:string,default:mixed}>
     */
    public static function definitions(): array
    {
        return [
            self::MAX_LISTINGS_TOTAL => [
                'type' => 'integer',
                'category' => 'listings',
                'label' => 'Maximo de anuncios',
                'description' => 'Cantidad total de anuncios que el mariachi puede tener activos o en gestion.',
                'default' => 1,
            ],
            self::LISTING_TERM_PRIMARY_MONTHS => [
                'type' => 'integer',
                'category' => 'pricing',
                'label' => 'Vigencia 1: meses',
                'description' => 'Primer plazo visible en el wizard del anuncio.',
                'default' => 1,
            ],
            self::LISTING_TERM_PRIMARY_DISCOUNT_PERCENT => [
                'type' => 'integer',
                'category' => 'pricing',
                'label' => 'Vigencia 1: descuento %',
                'description' => 'Descuento aplicado sobre el total de ese plazo.',
                'default' => 0,
            ],
            self::LISTING_TERM_SECONDARY_MONTHS => [
                'type' => 'integer',
                'category' => 'pricing',
                'label' => 'Vigencia 2: meses',
                'description' => 'Segundo plazo visible en el wizard del anuncio.',
                'default' => 3,
            ],
            self::LISTING_TERM_SECONDARY_DISCOUNT_PERCENT => [
                'type' => 'integer',
                'category' => 'pricing',
                'label' => 'Vigencia 2: descuento %',
                'description' => 'Descuento aplicado sobre el total de ese plazo.',
                'default' => 10,
            ],
            self::LISTING_TERM_TERTIARY_MONTHS => [
                'type' => 'integer',
                'category' => 'pricing',
                'label' => 'Vigencia 3: meses',
                'description' => 'Tercer plazo visible en el wizard del anuncio.',
                'default' => 12,
            ],
            self::LISTING_TERM_TERTIARY_DISCOUNT_PERCENT => [
                'type' => 'integer',
                'category' => 'pricing',
                'label' => 'Vigencia 3: descuento %',
                'description' => 'Descuento aplicado sobre el total de ese plazo.',
                'default' => 20,
            ],
            self::MAX_PHOTOS_PER_LISTING => [
                'type' => 'integer',
                'category' => 'media',
                'label' => 'Fotos por anuncio',
                'description' => 'Tope de fotos permitidas por anuncio.',
                'default' => 5,
            ],
            self::CAN_ADD_VIDEO => [
                'type' => 'boolean',
                'category' => 'media',
                'label' => 'Permite videos',
                'description' => 'Habilita la carga de videos en el anuncio.',
                'default' => false,
            ],
            self::MAX_VIDEOS_PER_LISTING => [
                'type' => 'integer',
                'category' => 'media',
                'label' => 'Videos por anuncio',
                'description' => 'Cantidad maxima de videos admitidos por anuncio.',
                'default' => 0,
            ],
            self::CAN_SHOW_WHATSAPP => [
                'type' => 'boolean',
                'category' => 'contact',
                'label' => 'Mostrar WhatsApp',
                'description' => 'Permite exponer el boton o numero de WhatsApp en el perfil publico.',
                'default' => false,
            ],
            self::CAN_SHOW_PHONE => [
                'type' => 'boolean',
                'category' => 'contact',
                'label' => 'Mostrar telefono',
                'description' => 'Permite exponer el telefono o boton de llamada en el perfil publico.',
                'default' => false,
            ],
            self::MAX_CITIES_COVERED => [
                'type' => 'integer',
                'category' => 'coverage',
                'label' => 'Ciudades incluidas',
                'description' => 'Cantidad de ciudades de cobertura incluidas antes de add-ons.',
                'default' => 1,
            ],
            self::MAX_ZONES_COVERED => [
                'type' => 'integer',
                'category' => 'coverage',
                'label' => 'Zonas por anuncio',
                'description' => 'Cantidad maxima de zonas o barrios configurables por anuncio.',
                'default' => 5,
            ],
            self::MAX_EVENT_TYPES => [
                'type' => 'integer',
                'category' => 'filters',
                'label' => 'Tipos de evento',
                'description' => 'Cantidad maxima de tipos de evento seleccionables por anuncio.',
                'default' => 3,
            ],
            self::MAX_SERVICE_TYPES => [
                'type' => 'integer',
                'category' => 'filters',
                'label' => 'Tipos de servicio',
                'description' => 'Cantidad maxima de tipos de servicio seleccionables por anuncio.',
                'default' => 1,
            ],
            self::MAX_GROUP_SIZES => [
                'type' => 'integer',
                'category' => 'filters',
                'label' => 'Tamanos de grupo',
                'description' => 'Cantidad maxima de tamanos de grupo seleccionables por anuncio.',
                'default' => 1,
            ],
            self::MAX_BUDGET_RANGES => [
                'type' => 'integer',
                'category' => 'filters',
                'label' => 'Rangos de presupuesto',
                'description' => 'Cantidad maxima de rangos de presupuesto seleccionables por anuncio.',
                'default' => 3,
            ],
            self::PRIORITY_LEVEL => [
                'type' => 'integer',
                'category' => 'visibility',
                'label' => 'Nivel de prioridad',
                'description' => 'Peso relativo para ranking interno y ordenamientos futuros.',
                'default' => 0,
            ],
            self::CAN_FEATURED_CITY => [
                'type' => 'boolean',
                'category' => 'visibility',
                'label' => 'Destacado en ciudad',
                'description' => 'Permite activar promociones o posiciones destacadas por ciudad.',
                'default' => false,
            ],
            self::CAN_FEATURED_HOME => [
                'type' => 'boolean',
                'category' => 'visibility',
                'label' => 'Destacado en home',
                'description' => 'Permite activar promociones o posiciones destacadas en home.',
                'default' => false,
            ],
            self::CAN_REQUEST_VERIFICATION => [
                'type' => 'boolean',
                'category' => 'extras',
                'label' => 'Permite verificacion',
                'description' => 'Habilita el flujo premium de verificacion del perfil.',
                'default' => false,
            ],
            self::HAS_PREMIUM_BADGE => [
                'type' => 'boolean',
                'category' => 'extras',
                'label' => 'Badge premium',
                'description' => 'Muestra distintivos premium en el marketplace.',
                'default' => false,
            ],
            self::HAS_ADVANCED_STATS => [
                'type' => 'boolean',
                'category' => 'extras',
                'label' => 'Estadisticas avanzadas',
                'description' => 'Habilita modulos de metricas avanzadas.',
                'default' => false,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function categoryLabels(): array
    {
        return [
            'pricing' => 'Precio y vigencia',
            'listings' => 'Listados',
            'media' => 'Media',
            'contact' => 'Contacto',
            'coverage' => 'Cobertura',
            'filters' => 'Filtros',
            'visibility' => 'Visibilidad y ranking',
            'extras' => 'Extras',
        ];
    }

    /**
     * @return array<string, array<string, array{type:string,category:string,label:string,description:string,default:mixed}>>
     */
    public static function groupedDefinitions(): array
    {
        $definitions = self::definitions();

        $grouped = [];

        foreach (self::categoryLabels() as $category => $label) {
            $grouped[$category] = array_filter(
                $definitions,
                static fn (array $definition): bool => $definition['category'] === $category
            );
        }

        return $grouped;
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        $defaults = [];

        foreach (self::definitions() as $key => $definition) {
            $defaults[$key] = $definition['default'];
        }

        return $defaults;
    }

    public static function defaultFor(string $key): mixed
    {
        return self::definitions()[$key]['default'] ?? null;
    }

    public static function typeFor(string $key): string
    {
        return self::definitions()[$key]['type'] ?? 'string';
    }

    public static function normalize(string $key, mixed $value): mixed
    {
        $type = self::typeFor($key);

        return match ($type) {
            'boolean' => (bool) $value,
            'integer' => max(0, (int) $value),
            'json' => is_array($value) ? $value : (array) $value,
            default => $value === null ? null : trim((string) $value),
        };
    }
}

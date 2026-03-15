<?php

namespace Database\Seeders;

use App\Models\MarketplaceCity;
use App\Models\MarketplaceZone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MarketplaceZoneSeederBogotaMedellin extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('marketplace_cities') || ! Schema::hasTable('marketplace_zones')) {
            return;
        }

        $bogota = $this->upsertCity('Bogotá', 'bogota', 1);
        $medellin = $this->upsertCity('Medellín', 'medellin', 2);
        $cali = $this->upsertCity('Cali', 'cali', 3);
        $barranquilla = $this->upsertCity('Barranquilla', 'barranquilla', 4);
        $cartagena = $this->upsertCity('Cartagena', 'cartagena', 5);

        $this->seedZones($bogota, [
            'Usaquén',
            'Chapinero',
            'Santa Fe',
            'San Cristóbal',
            'Usme',
            'Tunjuelito',
            'Bosa',
            'Kennedy',
            'Fontibón',
            'Engativá',
            'Suba',
            'Barrios Unidos',
            'Teusaquillo',
            'Los Mártires',
            'Antonio Nariño',
            'Puente Aranda',
            'La Candelaria',
            'Rafael Uribe Uribe',
            'Ciudad Bolívar',
            'Sumapaz',
        ]);

        $this->seedZones($medellin, [
            'Popular',
            'Santa Cruz',
            'Manrique',
            'Aranjuez',
            'Castilla',
            'Doce de Octubre',
            'Robledo',
            'Villa Hermosa',
            'Buenos Aires',
            'La Candelaria',
            'Laureles-Estadio',
            'La América',
            'San Javier',
            'El Poblado',
            'Guayabal',
            'Belén',
        ]);

        $this->seedZones($cali, [
            'Granada',
            'San Antonio',
            'El Peñón',
            'Ciudad Jardín',
            'Tequendama',
            'San Fernando',
            'Normandía',
            'Versalles',
        ]);

        $this->seedZones($barranquilla, [
            'Alto Prado',
            'El Prado',
            'Riomar',
            'Villa Santos',
            'Boston',
            'Bellavista',
            'Ciudad Jardín',
            'San Vicente',
        ]);

        $this->seedZones($cartagena, [
            'Bocagrande',
            'Centro Histórico',
            'Getsemaní',
            'Manga',
            'Castillogrande',
            'Crespo',
            'El Laguito',
            'La Boquilla',
        ]);
    }

    private function upsertCity(string $name, string $slug, int $sortOrder): MarketplaceCity
    {
        $city = MarketplaceCity::query()
            ->where('slug', $slug)
            ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->orWhereRaw('LOWER(name) = ?', [mb_strtolower(Str::ascii($name))])
            ->first();

        if (! $city) {
            return MarketplaceCity::query()->create([
                'name' => $name,
                'slug' => $slug,
                'sort_order' => $sortOrder,
                'is_featured' => false,
                'is_active' => true,
                'show_in_search' => true,
            ]);
        }

        $city->forceFill([
            'name' => $name,
            'slug' => $slug,
            'sort_order' => $sortOrder,
            'is_featured' => false,
            'is_active' => true,
            'show_in_search' => true,
        ])->save();

        return $city;
    }

    /**
     * @param  array<int, string>  $zones
     */
    private function seedZones(MarketplaceCity $city, array $zones): void
    {
        $officialSlugs = [];

        foreach ($zones as $index => $zoneName) {
            $slug = Str::slug($zoneName);
            $officialSlugs[] = $slug;

            MarketplaceZone::query()->updateOrCreate(
                [
                    'marketplace_city_id' => $city->id,
                    'slug' => $slug,
                ],
                [
                    'name' => $zoneName,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'show_in_search' => true,
                ]
            );
        }

        MarketplaceZone::query()
            ->where('marketplace_city_id', $city->id)
            ->whereNotIn('slug', $officialSlugs)
            ->update([
                'show_in_search' => false,
            ]);
    }
}

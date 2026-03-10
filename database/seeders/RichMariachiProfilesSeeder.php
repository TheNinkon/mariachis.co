<?php

namespace Database\Seeders;

use App\Models\BudgetRange;
use App\Models\EventType;
use App\Models\GroupSizeOption;
use App\Models\MariachiProfile;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\MariachiProfileProgressService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RichMariachiProfilesSeeder extends Seeder
{
    public function run(): void
    {
        $eventTypeIds = EventType::query()->pluck('id', 'name');
        $serviceTypeIds = ServiceType::query()->pluck('id', 'name');
        $groupSizeIds = GroupSizeOption::query()->pluck('id', 'name');
        $budgetRangeIds = BudgetRange::query()->pluck('id', 'name');

        foreach ($this->profilesData() as $item) {
            $user = User::updateOrCreate(
                ['email' => $item['email']],
                [
                    'name' => $item['first_name'].' '.$item['last_name'],
                    'first_name' => $item['first_name'],
                    'last_name' => $item['last_name'],
                    'phone' => $item['phone'],
                    'password' => 'Mariachi12345!',
                    'role' => User::ROLE_MARIACHI,
                    'status' => User::STATUS_ACTIVE,
                    'email_verified_at' => now(),
                ]
            );

            $profile = MariachiProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'city_name' => $item['city_name'],
                    'country' => $item['country'],
                    'state' => $item['state'],
                    'postal_code' => $item['postal_code'],
                    'address' => $item['address'],
                    'latitude' => $item['latitude'],
                    'longitude' => $item['longitude'],
                    'whatsapp' => $item['whatsapp'],
                    'business_name' => $item['business_name'],
                    'responsible_name' => $item['responsible_name'],
                    'short_description' => $item['short_description'],
                    'full_description' => $item['full_description'],
                    'base_price' => $item['base_price'],
                    'website' => $item['website'],
                    'instagram' => $item['instagram'],
                    'facebook' => $item['facebook'],
                    'tiktok' => $item['tiktok'],
                    'youtube' => $item['youtube'],
                    'travels_to_other_cities' => true,
                    'profile_completion' => 100,
                    'profile_completed' => true,
                    'stage_status' => 'profile_complete',
                    'slug' => $item['slug'],
                ]
            );

            $profile->eventTypes()->sync($this->mapNamesToIds($item['event_types'], $eventTypeIds->all()));
            $profile->serviceTypes()->sync($this->mapNamesToIds($item['service_types'], $serviceTypeIds->all()));
            $profile->groupSizeOptions()->sync($this->mapNamesToIds($item['group_sizes'], $groupSizeIds->all()));
            $profile->budgetRanges()->sync($this->mapNamesToIds($item['budget_ranges'], $budgetRangeIds->all()));

            $profile->serviceAreas()->delete();
            foreach ($item['coverage_areas'] as $area) {
                $profile->serviceAreas()->create(['city_name' => $area]);
            }

            $profile->videos()->delete();
            foreach ($item['videos'] as $url) {
                $profile->videos()->create([
                    'url' => $url,
                    'platform' => Str::contains($url, ['youtube.com', 'youtu.be']) ? 'youtube' : 'external',
                ]);
            }

            $this->syncPhotos($profile, $item['photos']);
            app(MariachiProfileProgressService::class)->refresh($profile);
        }
    }

    /**
     * @param array<int, string> $names
     * @param array<string, int> $map
     * @return array<int, int>
     */
    private function mapNamesToIds(array $names, array $map): array
    {
        return collect($names)
            ->map(fn (string $name): ?int => Arr::get($map, $name))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $photoFiles
     */
    private function syncPhotos(MariachiProfile $profile, array $photoFiles): void
    {
        $disk = Storage::disk('public');

        foreach ($profile->photos as $existingPhoto) {
            $disk->delete($existingPhoto->path);
        }

        $profile->photos()->delete();

        foreach ($photoFiles as $index => $sourceFile) {
            $sourcePath = public_path('marketplace/img/'.$sourceFile);
            if (! is_file($sourcePath)) {
                continue;
            }

            $targetPath = 'mariachi-photos/seed/'.($profile->slug ?: 'mariachi').'-'.($index + 1).'-'.basename($sourceFile);
            $disk->put($targetPath, (string) file_get_contents($sourcePath));

            $profile->photos()->create([
                'path' => $targetPath,
                'title' => 'Galeria '.($index + 1),
                'sort_order' => $index + 1,
                'is_featured' => $index === 0,
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function profilesData(): array
    {
        $cities = [
            [
                'slug' => 'bogota',
                'city_name' => 'Bogota',
                'state' => 'Bogota D.C.',
                'postal' => '110111',
                'lat' => 4.6486,
                'lng' => -74.0628,
                'areas' => ['Chapinero', 'Usaquen', 'Suba', 'Teusaquillo', 'Kennedy'],
            ],
            [
                'slug' => 'medellin',
                'city_name' => 'Medellin',
                'state' => 'Antioquia',
                'postal' => '050021',
                'lat' => 6.2442,
                'lng' => -75.5812,
                'areas' => ['El Poblado', 'Laureles', 'Envigado', 'Belen', 'Sabaneta'],
            ],
            [
                'slug' => 'cali',
                'city_name' => 'Cali',
                'state' => 'Valle del Cauca',
                'postal' => '760001',
                'lat' => 3.4516,
                'lng' => -76.5320,
                'areas' => ['Granada', 'Ciudad Jardin', 'San Fernando', 'Versalles', 'Pance'],
            ],
            [
                'slug' => 'barranquilla',
                'city_name' => 'Barranquilla',
                'state' => 'Atlantico',
                'postal' => '080001',
                'lat' => 10.9685,
                'lng' => -74.7813,
                'areas' => ['Riomar', 'Soledad', 'Norte Centro Historico', 'Alto Prado', 'Villa Carolina'],
            ],
            [
                'slug' => 'cartagena',
                'city_name' => 'Cartagena',
                'state' => 'Bolivar',
                'postal' => '130001',
                'lat' => 10.3997,
                'lng' => -75.5144,
                'areas' => ['Bocagrande', 'Centro Historico', 'Manga', 'Crespo', 'Getsemani'],
            ],
        ];

        $variants = [
            [
                'brand_prefix' => 'Mariachi Imperial',
                'responsible' => ['Juan', 'Macias'],
                'event_types' => ['Bodas', 'Aniversarios', 'Serenatas', 'Eventos corporativos'],
                'service_types' => ['Show completo', 'A domicilio', 'Mariachi por horas'],
                'group_sizes' => ['5 integrantes', '7 integrantes', 'Mariachi completo'],
                'budget_ranges' => ['Estandar', 'Premium'],
                'price' => 360000,
                'short' => 'Serenatas y shows completos para bodas, aniversarios y eventos empresariales.',
                'full' => 'Formato profesional con repertorio clasico y moderno, coordinacion por WhatsApp y cobertura amplia para eventos privados y corporativos.',
                'photos' => ['1.webp', '2.webp', '6.jpeg'],
                'video' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
            ],
            [
                'brand_prefix' => 'Sol de Oro',
                'responsible' => ['Camilo', 'Rojas'],
                'event_types' => ['Bodas', 'Cumpleanos', 'Eventos corporativos'],
                'service_types' => ['Show completo', 'Servicio personalizado'],
                'group_sizes' => ['5 integrantes', '7 integrantes'],
                'budget_ranges' => ['Estandar', 'Premium'],
                'price' => 420000,
                'short' => 'Show elegante para bodas y celebraciones con repertorio a medida.',
                'full' => 'Propuesta premium para eventos con alta exigencia, con entrada sorpresa y guion musical segun el tipo de celebracion.',
                'photos' => ['3.webp', '4.webp', '7.jpeg'],
                'video' => 'https://www.youtube.com/watch?v=jNQXAC9IVRw',
            ],
            [
                'brand_prefix' => 'Mariachi Amanecer',
                'responsible' => ['Andres', 'Valderrama'],
                'event_types' => ['Cumpleanos', 'Aniversarios', 'Serenatas', 'Fiestas privadas'],
                'service_types' => ['A domicilio', 'Serenata sorpresa', 'Mariachi por horas'],
                'group_sizes' => ['3 integrantes', '4 integrantes', '5 integrantes'],
                'budget_ranges' => ['Economico', 'Estandar'],
                'price' => 270000,
                'short' => 'Ideal para serenatas familiares y celebraciones intimas con respuesta rapida.',
                'full' => 'Equipo enfocado en celebraciones familiares, fechas especiales y homenajes con repertorio flexible y puntualidad en el servicio.',
                'photos' => ['8.jpg', '9.jpg', '5.jpeg'],
                'video' => 'https://www.youtube.com/watch?v=ScMzIvxBSi4',
            ],
        ];

        $profiles = [];

        foreach ($cities as $city) {
            foreach ($variants as $index => $variant) {
                $business = $variant['brand_prefix'].' '.$city['city_name'];
                $slug = Str::slug($variant['brand_prefix'].' '.$city['slug'].' '.($index + 1));
                $email = Str::slug($variant['brand_prefix'], '.').'.'.($index + 1).'.'.$city['slug'].'@mariachis.co';
                $citySeed = (int) (crc32($city['slug']) % 10000);
                $phoneBase = '57'.(310 + $index).str_pad((string) ($citySeed + (($index + 1) * 1111)), 7, '0', STR_PAD_LEFT);

                $profiles[] = [
                    'slug' => $slug,
                    'business_name' => $business,
                    'responsible_name' => $variant['responsible'][0].' '.$variant['responsible'][1],
                    'first_name' => $variant['responsible'][0],
                    'last_name' => $variant['responsible'][1],
                    'email' => $email,
                    'phone' => '+'.$phoneBase,
                    'whatsapp' => '+'.$phoneBase,
                    'city_name' => $city['city_name'],
                    'state' => $city['state'],
                    'country' => 'Colombia',
                    'postal_code' => $city['postal'],
                    'address' => $city['areas'][0].', '.$city['city_name'],
                    'latitude' => $city['lat'] + ($index * 0.01),
                    'longitude' => $city['lng'] + ($index * 0.01),
                    'short_description' => $variant['short'],
                    'full_description' => $variant['full'].' Cobertura principal en '.$city['city_name'].' y zonas cercanas.',
                    'base_price' => $variant['price'] + ($index * 25000),
                    'website' => 'https://mariachis.co/'.$slug,
                    'instagram' => 'https://instagram.com/'.Str::slug($business, ''),
                    'facebook' => 'https://facebook.com/'.Str::slug($business, ''),
                    'tiktok' => 'https://tiktok.com/@'.Str::slug($business, ''),
                    'youtube' => $variant['video'],
                    'event_types' => $variant['event_types'],
                    'service_types' => $variant['service_types'],
                    'group_sizes' => $variant['group_sizes'],
                    'budget_ranges' => $variant['budget_ranges'],
                    'coverage_areas' => array_slice($city['areas'], 0, 3),
                    'videos' => [$variant['video']],
                    'photos' => $variant['photos'],
                ];
            }
        }

        return $profiles;
    }
}

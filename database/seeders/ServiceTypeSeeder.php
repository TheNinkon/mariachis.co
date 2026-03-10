<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'A domicilio', 'icon' => 'home'],
            ['name' => 'Show completo', 'icon' => 'microphone'],
            ['name' => 'Mariachi por horas', 'icon' => 'clock'],
            ['name' => 'Serenata sorpresa', 'icon' => 'gift'],
            ['name' => 'Servicio personalizado', 'icon' => 'settings'],
        ];

        foreach ($items as $index => $item) {
            ServiceType::updateOrCreate(
                ['name' => $item['name']],
                [
                    'slug' => Str::slug($item['name']),
                    'icon' => $item['icon'],
                    'sort_order' => $index + 1,
                    'is_featured' => false,
                    'is_active' => true,
                ]
            );
        }
    }
}

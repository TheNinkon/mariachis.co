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
            ['name' => 'A domicilio', 'icon' => 'home', 'is_visible_in_home' => true, 'home_priority' => 10],
            ['name' => 'Show completo', 'icon' => 'microphone', 'is_visible_in_home' => true, 'home_priority' => 20],
            ['name' => 'Mariachi por horas', 'icon' => 'clock', 'is_visible_in_home' => true, 'home_priority' => 30],
            ['name' => 'Serenata sorpresa', 'icon' => 'gift', 'is_visible_in_home' => true, 'home_priority' => 40],
            ['name' => 'Servicio personalizado', 'icon' => 'settings', 'is_visible_in_home' => true, 'home_priority' => 50],
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
                    'is_visible_in_home' => $item['is_visible_in_home'],
                    'home_priority' => $item['home_priority'],
                    'home_clicks_count' => 0,
                ]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\EventType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EventTypeSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Bodas', 'icon' => 'rings', 'is_visible_in_home' => true, 'home_priority' => 10],
            ['name' => 'Cumpleanos', 'icon' => 'cake', 'is_visible_in_home' => true, 'home_priority' => 20],
            ['name' => 'Aniversarios', 'icon' => 'sparkles', 'is_visible_in_home' => true, 'home_priority' => 30],
            ['name' => 'Serenatas', 'icon' => 'music-note', 'is_visible_in_home' => true, 'home_priority' => 40],
            ['name' => 'Eventos corporativos', 'icon' => 'briefcase', 'is_visible_in_home' => true, 'home_priority' => 50],
            ['name' => 'Dia de la madre', 'icon' => 'flower', 'is_visible_in_home' => false, 'home_priority' => 999],
            ['name' => 'Fiestas privadas', 'icon' => 'party', 'is_visible_in_home' => true, 'home_priority' => 60],
        ];

        foreach ($items as $index => $item) {
            EventType::updateOrCreate(
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

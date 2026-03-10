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
            ['name' => 'Bodas', 'icon' => 'rings'],
            ['name' => 'Cumpleanos', 'icon' => 'cake'],
            ['name' => 'Aniversarios', 'icon' => 'sparkles'],
            ['name' => 'Serenatas', 'icon' => 'music-note'],
            ['name' => 'Eventos corporativos', 'icon' => 'briefcase'],
            ['name' => 'Dia de la madre', 'icon' => 'flower'],
            ['name' => 'Fiestas privadas', 'icon' => 'party'],
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
                ]
            );
        }
    }
}

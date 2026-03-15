<?php

namespace Database\Seeders;

use App\Models\GroupSizeOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GroupSizeOptionSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => '3 integrantes', 'sort_order' => 1, 'icon' => 'users-3', 'is_visible_in_home' => true, 'home_priority' => 10],
            ['name' => '4 integrantes', 'sort_order' => 2, 'icon' => 'users-4', 'is_visible_in_home' => true, 'home_priority' => 20],
            ['name' => '5 integrantes', 'sort_order' => 3, 'icon' => 'users-5', 'is_visible_in_home' => true, 'home_priority' => 30],
            ['name' => '7 integrantes', 'sort_order' => 4, 'icon' => 'users-7', 'is_visible_in_home' => true, 'home_priority' => 40],
            ['name' => 'Mariachi completo', 'sort_order' => 5, 'icon' => 'users-group', 'is_visible_in_home' => true, 'home_priority' => 50],
        ];

        foreach ($items as $item) {
            GroupSizeOption::updateOrCreate(
                ['name' => $item['name']],
                [
                    'slug' => Str::slug($item['name']),
                    'icon' => $item['icon'],
                    'sort_order' => $item['sort_order'],
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

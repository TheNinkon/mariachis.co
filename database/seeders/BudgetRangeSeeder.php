<?php

namespace Database\Seeders;

use App\Models\BudgetRange;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BudgetRangeSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Economico', 'icon' => 'wallet', 'is_visible_in_home' => true, 'home_priority' => 10],
            ['name' => 'Estandar', 'icon' => 'coins', 'is_visible_in_home' => true, 'home_priority' => 20],
            ['name' => 'Premium', 'icon' => 'diamond', 'is_visible_in_home' => true, 'home_priority' => 30],
        ];

        foreach ($items as $index => $item) {
            BudgetRange::updateOrCreate(
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

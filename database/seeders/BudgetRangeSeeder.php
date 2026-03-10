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
            ['name' => 'Economico', 'icon' => 'wallet'],
            ['name' => 'Estandar', 'icon' => 'coins'],
            ['name' => 'Premium', 'icon' => 'diamond'],
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
                ]
            );
        }
    }
}

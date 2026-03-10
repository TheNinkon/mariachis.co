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
            ['name' => '3 integrantes', 'sort_order' => 1, 'icon' => 'users-3'],
            ['name' => '4 integrantes', 'sort_order' => 2, 'icon' => 'users-4'],
            ['name' => '5 integrantes', 'sort_order' => 3, 'icon' => 'users-5'],
            ['name' => '7 integrantes', 'sort_order' => 4, 'icon' => 'users-7'],
            ['name' => 'Mariachi completo', 'sort_order' => 5, 'icon' => 'users-group'],
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
                ]
            );
        }
    }
}

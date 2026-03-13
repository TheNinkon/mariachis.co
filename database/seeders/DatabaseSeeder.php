<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            EventTypeSeeder::class,
            ServiceTypeSeeder::class,
            GroupSizeOptionSeeder::class,
            BudgetRangeSeeder::class,
            AdminUserSeeder::class,
            StaffUserSeeder::class,
            RichMariachiProfilesSeeder::class,
            MarketplaceLocationSeeder::class,
            MarketplaceZoneSeederBogotaMedellin::class,
            ClientUserSeeder::class,
            BlogPostSeeder::class,
        ]);
    }
}

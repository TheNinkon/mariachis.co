<?php

namespace Tests\Feature;

use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoMarketplaceListingsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_marketplace_listings_are_created_and_synced_idempotently(): void
    {
        Storage::fake('public');

        $this->seed(\Database\Seeders\DemoMarketplaceListingsSeeder::class);

        $this->assertSame(15, MariachiListing::query()->count());
        $this->assertSame(15, MariachiListing::query()->published()->count());
        $this->assertGreaterThanOrEqual(5, MariachiProfile::query()->where('verification_status', 'verified')->count());
        $this->assertEqualsCanonicalizing(
            ['basic', 'premium', 'pro'],
            MariachiListing::query()->distinct()->pluck('selected_plan_code')->all()
        );

        $listingIds = MariachiListing::query()->orderBy('id')->pluck('id')->all();

        $this->seed(\Database\Seeders\DemoMarketplaceListingsSeeder::class);

        $this->assertSame(15, MariachiListing::query()->count());
        $this->assertSame(15, MariachiListing::query()->published()->count());
        $this->assertSame($listingIds, MariachiListing::query()->orderBy('id')->pluck('id')->all());
    }
}

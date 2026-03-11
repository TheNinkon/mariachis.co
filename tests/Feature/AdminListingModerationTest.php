<?php

namespace Tests\Feature;

use App\Models\MariachiListing;
use App\Models\User;
use Tests\TestCase;

class AdminListingModerationTest extends TestCase
{
    public function test_admin_can_open_listing_moderation_pages_and_approve_a_listing(): void
    {
        $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();
        $mariachi = User::query()->where('role', User::ROLE_MARIACHI)->firstOrFail();

        $profile = $mariachi->mariachiProfile()->firstOrCreate([], [
            'city_name' => 'Pendiente',
            'profile_completed' => false,
            'profile_completion' => 0,
            'stage_status' => 'provider_incomplete',
            'verification_status' => 'unverified',
            'subscription_plan_code' => 'basic',
            'subscription_listing_limit' => 1,
            'subscription_active' => true,
        ]);

        $title = 'Listado moderacion admin '.uniqid();

        $listing = $profile->listings()->create([
            'title' => $title,
            'short_description' => 'Prueba de moderacion',
            'description' => 'Contenido de prueba',
            'base_price' => 150000,
            'country' => 'Colombia',
            'state' => 'Atlantico',
            'city_name' => 'Barranquilla',
            'address' => 'Cra 1 # 1-1',
            'latitude' => 10.9876543,
            'longitude' => -74.7999999,
            'listing_completion' => 100,
            'listing_completed' => true,
            'status' => MariachiListing::STATUS_ACTIVE,
            'review_status' => MariachiListing::REVIEW_PENDING,
            'is_active' => true,
            'selected_plan_code' => 'basic',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.listings.index'))
            ->assertOk()
            ->assertSee('Moderacion de anuncios', false);

        $this->actingAs($admin)
            ->get(route('admin.listings.show', $listing))
            ->assertOk()
            ->assertSee($title, false);

        $this->actingAs($admin)
            ->patch(route('admin.listings.moderate', $listing), [
                'action' => 'approve',
            ])
            ->assertRedirect(route('admin.listings.show', $listing));

        $this->assertSame(MariachiListing::REVIEW_APPROVED, $listing->fresh()->review_status);
    }
}

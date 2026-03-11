<?php

namespace Tests\Feature;

use App\Models\BudgetRange;
use App\Models\EventType;
use App\Models\GroupSizeOption;
use App\Models\MariachiListing;
use App\Models\ServiceType;
use App\Models\User;
use Tests\TestCase;

class MariachiListingReviewWorkflowTest extends TestCase
{
    public function test_mariachi_can_submit_a_completed_listing_for_review(): void
    {
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

        $title = 'Listado para revision '.uniqid();

        $listing = $profile->listings()->create([
            'title' => $title,
            'short_description' => 'Descripcion corta para revision',
            'description' => 'Descripcion completa para revision',
            'base_price' => 220000,
            'country' => 'Colombia',
            'state' => 'Atlantico',
            'city_name' => 'Barranquilla',
            'address' => 'Cra 10 # 20-30',
            'latitude' => 10.9876543,
            'longitude' => -74.7999999,
            'travels_to_other_cities' => false,
            'listing_completion' => 0,
            'listing_completed' => false,
            'status' => MariachiListing::STATUS_ACTIVE,
            'review_status' => MariachiListing::REVIEW_DRAFT,
            'is_active' => true,
            'selected_plan_code' => 'basic',
        ]);

        $listing->photos()->create([
            'path' => 'testing/listing-photo.jpg',
            'sort_order' => 1,
            'is_featured' => true,
        ]);

        $eventTypeId = EventType::query()->where('is_active', true)->value('id');
        $serviceTypeId = ServiceType::query()->where('is_active', true)->value('id');
        $groupSizeOptionId = GroupSizeOption::query()->where('is_active', true)->value('id');
        $budgetRangeId = BudgetRange::query()->where('is_active', true)->value('id');

        $this->assertNotNull($eventTypeId);
        $this->assertNotNull($serviceTypeId);
        $this->assertNotNull($groupSizeOptionId);
        $this->assertNotNull($budgetRangeId);

        $listing->eventTypes()->sync([(int) $eventTypeId]);
        $listing->serviceTypes()->sync([(int) $serviceTypeId]);
        $listing->groupSizeOptions()->sync([(int) $groupSizeOptionId]);
        $listing->budgetRanges()->sync([(int) $budgetRangeId]);

        $this->actingAs($mariachi)
            ->post(route('mariachi.listings.submit-review', $listing))
            ->assertRedirect();

        $listing->refresh();

        $this->assertTrue($listing->listing_completed);
        $this->assertSame(MariachiListing::REVIEW_PENDING, $listing->review_status);
        $this->assertNotNull($listing->submitted_for_review_at);
    }
}

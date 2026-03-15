<?php

namespace Tests\Feature;

use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\ListingInfoRequest;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_response_includes_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_listing_info_request_rejects_untrusted_origin(): void
    {
        $listing = $this->createPublishedListing();

        $response = $this
            ->withHeader('Origin', 'https://evil.example')
            ->post(route('listing.info-requests.store', $listing->slug), $this->validListingInfoPayload());

        $response->assertForbidden();
    }

    public function test_listing_info_request_is_sanitized_and_rate_limited(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $listing = $this->createPublishedListing();

        $response = $this->post(route('listing.info-requests.store', $listing->slug), [
            'name' => ' <b>Juan Perez</b> ',
            'email' => ' JUAN@example.com ',
            'phone' => ' <script>3001231234</script> ',
            'event_date' => now()->addWeek()->toDateString(),
            'event_city' => ' <i>Bogota</i> ',
            'message' => '<script>alert(1)</script> Hola     mundo ',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('listing_info_requests', [
            'mariachi_listing_id' => $listing->id,
            'status' => ListingInfoRequest::STATUS_NEW,
            'name' => 'Juan Perez',
            'email' => 'juan@example.com',
            'phone' => '3001231234',
            'event_city' => 'Bogota',
            'message' => 'alert(1) Hola mundo',
        ]);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('listing.info-requests.store', $listing->slug), $this->validListingInfoPayload())
                ->assertCreated();
        }

        $this->post(route('listing.info-requests.store', $listing->slug), $this->validListingInfoPayload())
            ->assertStatus(429);
    }

    public function test_admin_login_is_rate_limited(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-123'),
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        foreach (range(1, 5) as $attempt) {
            $response = $this
                ->withServerVariables(['HTTP_HOST' => config('domains.admin')])
                ->from(route('login'))
                ->post(route('login.attempt'), [
                    'email' => 'admin@example.com',
                    'password' => 'bad-password',
                ]);

            $response->assertSessionHasErrors('email');
        }

        $this
            ->withServerVariables(['HTTP_HOST' => config('domains.admin')])
            ->from(route('login'))
            ->post(route('login.attempt'), [
                'email' => 'admin@example.com',
                'password' => 'bad-password',
            ])->assertStatus(429);
    }

    private function createPublishedListing(): MariachiListing
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);

        $profile = MariachiProfile::query()->create([
            'user_id' => $user->id,
            'slug' => 'mariachi-hardening-prueba',
            'city_name' => 'Bogota',
            'business_name' => 'Mariachi Hardening',
            'responsible_name' => 'Responsable',
            'short_description' => 'Show para eventos.',
            'profile_completed' => true,
            'profile_completion' => 100,
            'stage_status' => 'profile_complete',
        ]);

        return MariachiListing::query()->create([
            'mariachi_profile_id' => $profile->id,
            'slug' => 'mariachi-hardening-prueba-listing',
            'title' => 'Mariachi Hardening',
            'short_description' => 'Show para eventos.',
            'city_name' => 'Bogota',
            'status' => MariachiListing::STATUS_ACTIVE,
            'review_status' => MariachiListing::REVIEW_APPROVED,
            'payment_status' => MariachiListing::PAYMENT_APPROVED,
            'is_active' => true,
            'listing_completed' => true,
            'listing_completion' => 100,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function validListingInfoPayload(): array
    {
        return [
            'name' => 'Juan Perez',
            'email' => 'juan@example.com',
            'phone' => '3001231234',
            'event_date' => now()->addWeek()->toDateString(),
            'event_city' => 'Bogota',
            'message' => 'Hola, quiero una cotización.',
        ];
    }
}

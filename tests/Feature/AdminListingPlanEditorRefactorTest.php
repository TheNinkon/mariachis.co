<?php

namespace Tests\Feature;

use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminListingPlanEditorRefactorTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_plan_editor_uses_new_listing_language_and_hides_legacy_verification_fields(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $plan = Plan::query()->where('code', 'premium')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.plans.edit', $plan))
            ->assertOk()
            ->assertSee('Borradores maximos')
            ->assertSee('Anuncios publicados permitidos')
            ->assertSee('Insignia comercial del plan')
            ->assertDontSee('Permite verificacion')
            ->assertDontSee('Maximo de anuncios')
            ->assertDontSee('Verificacion de perfil (legado)')
            ->assertDontSee('Anuncios totales (legado)');
    }

    public function test_public_listing_and_provider_show_verified_badges_only_for_active_verification(): void
    {
        $mariachi = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);

        $profile = MariachiProfile::query()->create([
            'user_id' => $mariachi->id,
            'city_name' => 'Bogota',
            'business_name' => 'Mariachi Verificado',
            'responsible_name' => 'Juan Perez',
            'short_description' => 'Perfil verificado para pruebas',
            'profile_completed' => true,
            'profile_completion' => 100,
            'stage_status' => 'provider_ready',
            'subscription_plan_code' => 'premium',
            'subscription_listing_limit' => 0,
            'subscription_active' => true,
            'verification_status' => 'verified',
            'verification_expires_at' => now()->addWeek(),
            'slug' => 'mariachi-verificado',
            'slug_locked' => true,
        ]);

        $listing = MariachiListing::query()->create([
            'mariachi_profile_id' => $profile->id,
            'slug' => 'mariachi-verificado-bogota',
            'title' => 'Mariachi Verificado Bogota',
            'short_description' => 'Anuncio listo para pruebas',
            'city_name' => 'Bogota',
            'status' => MariachiListing::STATUS_ACTIVE,
            'review_status' => MariachiListing::REVIEW_APPROVED,
            'payment_status' => MariachiListing::PAYMENT_APPROVED,
            'is_active' => true,
            'listing_completed' => true,
            'listing_completion' => 100,
            'selected_plan_code' => 'premium',
            'base_price' => 520000,
        ]);

        $this->get(route('mariachi.public.show', ['slug' => $listing->slug]))
            ->assertOk()
            ->assertSee('<span class="listing-verified-badge"', false);

        $this->get(route('mariachi.provider.public.show', ['handle' => $profile->slug]))
            ->assertOk()
            ->assertSee('<span class="provider-profile-verified-badge">', false)
            ->assertSee('class="provider-profile-status-badge provider-profile-status-badge--verified"', false);

        $profile->update([
            'verification_expires_at' => now()->subDay(),
        ]);

        $this->get(route('mariachi.public.show', ['slug' => $listing->slug]))
            ->assertOk()
            ->assertDontSee('<span class="listing-verified-badge"', false);

        $this->get(route('mariachi.provider.public.show', ['handle' => $profile->slug]))
            ->assertOk()
            ->assertDontSee('<span class="provider-profile-verified-badge">', false)
            ->assertDontSee('class="provider-profile-status-badge provider-profile-status-badge--verified"', false)
            ->assertSee('Publicado');
    }
}

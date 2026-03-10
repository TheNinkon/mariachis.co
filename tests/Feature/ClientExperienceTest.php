<?php

namespace Tests\Feature;

use App\Models\MariachiProfile;
use App\Models\QuoteConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_client_login_from_client_panel(): void
    {
        $response = $this->get('/cliente/panel');

        $response->assertRedirect(route('client.login'));
    }

    public function test_client_can_create_quote_conversation_from_public_listing(): void
    {
        $client = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'status' => User::STATUS_ACTIVE,
        ]);

        $mariachiUser = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);

        $profile = MariachiProfile::query()->create([
            'user_id' => $mariachiUser->id,
            'slug' => 'mariachi-ensayo',
            'business_name' => 'Mariachi Ensayo',
            'city_name' => 'Bogota',
            'country' => 'Colombia',
            'state' => 'Bogota D.C.',
            'postal_code' => '110111',
            'address' => 'Calle 123',
            'latitude' => 4.71,
            'longitude' => -74.07,
            'responsible_name' => 'Test',
            'short_description' => 'Test',
            'full_description' => 'Test',
            'base_price' => 300000,
            'profile_completed' => true,
            'profile_completion' => 100,
            'stage_status' => 'profile_complete',
        ]);

        $response = $this->actingAs($client)->post('/mariachi/'.$profile->slug.'/solicitar-presupuesto', [
            'contact_phone' => '3001112233',
            'event_date' => now()->addWeek()->toDateString(),
            'event_city' => 'Bogota',
            'event_notes' => 'Necesito serenata para aniversario.',
        ]);

        $response->assertRedirect(route('client.dashboard', ['tab' => 'solicitudes']));
        $this->assertDatabaseCount('quote_conversations', 1);
        $this->assertDatabaseCount('quote_messages', 1);

        $conversation = QuoteConversation::query()->first();
        $this->assertSame($client->id, $conversation->client_user_id);
        $this->assertSame($profile->id, $conversation->mariachi_profile_id);
    }
}

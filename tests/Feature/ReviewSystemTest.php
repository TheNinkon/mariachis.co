<?php

namespace Tests\Feature;

use App\Models\MariachiProfile;
use App\Models\MariachiReview;
use App\Models\QuoteConversation;
use App\Models\QuoteMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_create_review_from_replied_conversation_and_cannot_duplicate_it(): void
    {
        [$client, $mariachiUser, $profile, $conversation] = $this->createConversationWithReply();

        $response = $this->actingAs($client)
            ->from('/mi-cuenta/solicitudes')
            ->post(route('client.reviews.store', ['conversation' => $conversation->id]), [
                'rating' => 5,
                'title' => 'Gran experiencia',
                'comment' => 'Muy puntuales, amables y el show estuvo excelente.',
                'event_type' => 'Cumpleanos',
            ]);

        $response->assertRedirect('/mi-cuenta/solicitudes');

        $this->assertDatabaseHas('mariachi_reviews', [
            'quote_conversation_id' => $conversation->id,
            'client_user_id' => $client->id,
            'mariachi_profile_id' => $profile->id,
            'moderation_status' => MariachiReview::STATUS_PENDING,
            'verification_status' => MariachiReview::VERIFICATION_BASIC,
            'is_visible' => false,
        ]);

        $secondAttempt = $this->actingAs($client)
            ->from('/mi-cuenta/solicitudes')
            ->post(route('client.reviews.store', ['conversation' => $conversation->id]), [
                'rating' => 4,
                'comment' => 'Segundo intento',
            ]);

        $secondAttempt->assertSessionHasErrors('review');
        $this->assertDatabaseCount('mariachi_reviews', 1);
    }

    public function test_admin_can_approve_review_and_it_appears_on_public_profile(): void
    {
        [$client, $mariachiUser, $profile, $conversation] = $this->createConversationWithReply();

        $review = MariachiReview::query()->create([
            'quote_conversation_id' => $conversation->id,
            'client_user_id' => $client->id,
            'mariachi_profile_id' => $profile->id,
            'rating' => 5,
            'title' => 'Excelente servicio',
            'comment' => 'Todo salio perfecto en nuestro evento familiar.',
            'moderation_status' => MariachiReview::STATUS_PENDING,
            'verification_status' => MariachiReview::VERIFICATION_BASIC,
            'is_visible' => false,
        ]);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $moderationResponse = $this->actingAs($admin)->patch(route('admin.reviews.moderate', ['review' => $review->id]), [
            'action' => 'approve',
        ]);

        $moderationResponse->assertRedirect();

        $this->assertDatabaseHas('mariachi_reviews', [
            'id' => $review->id,
            'moderation_status' => MariachiReview::STATUS_APPROVED,
            'is_visible' => true,
        ]);

        $publicResponse = $this->get('/mariachi/'.$profile->slug);

        $publicResponse->assertOk();
        $publicResponse->assertSee('Excelente servicio');
        $publicResponse->assertSee('Todo salio perfecto en nuestro evento familiar.');
    }

    public function test_mariachi_can_reply_and_report_review(): void
    {
        [$client, $mariachiUser, $profile, $conversation] = $this->createConversationWithReply();

        $review = MariachiReview::query()->create([
            'quote_conversation_id' => $conversation->id,
            'client_user_id' => $client->id,
            'mariachi_profile_id' => $profile->id,
            'rating' => 3,
            'comment' => 'Buena experiencia general.',
            'moderation_status' => MariachiReview::STATUS_APPROVED,
            'verification_status' => MariachiReview::VERIFICATION_BASIC,
            'is_visible' => true,
        ]);

        $replyResponse = $this->actingAs($mariachiUser)->post(route('mariachi.reviews.reply', ['review' => $review->id]), [
            'reply' => 'Gracias por confiar en nosotros.',
        ]);

        $replyResponse->assertRedirect();

        $this->assertDatabaseHas('mariachi_reviews', [
            'id' => $review->id,
            'mariachi_reply' => 'Gracias por confiar en nosotros.',
        ]);

        $reportResponse = $this->actingAs($mariachiUser)->post(route('mariachi.reviews.report', ['review' => $review->id]), [
            'reason' => 'Detectamos informacion falsa en esta resena.',
        ]);

        $reportResponse->assertRedirect();

        $this->assertDatabaseHas('mariachi_reviews', [
            'id' => $review->id,
            'moderation_status' => MariachiReview::STATUS_REPORTED,
            'is_visible' => false,
        ]);
    }

    /**
     * @return array{0:User,1:User,2:MariachiProfile,3:QuoteConversation}
     */
    private function createConversationWithReply(): array
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
            'slug' => 'mariachi-opiniones',
            'business_name' => 'Mariachi Opiniones',
            'city_name' => 'Bogota',
            'country' => 'Colombia',
            'state' => 'Bogota D.C.',
            'postal_code' => '110111',
            'address' => 'Calle 45',
            'latitude' => 4.71,
            'longitude' => -74.07,
            'responsible_name' => 'Responsable',
            'short_description' => 'Perfil de prueba para resenas',
            'full_description' => 'Descripcion completa para pruebas de reseñas.',
            'base_price' => 400000,
            'profile_completed' => true,
            'profile_completion' => 100,
            'stage_status' => 'profile_complete',
        ]);

        $conversation = QuoteConversation::query()->create([
            'client_user_id' => $client->id,
            'mariachi_profile_id' => $profile->id,
            'status' => QuoteConversation::STATUS_IN_PROGRESS,
            'event_date' => now()->subDays(2)->toDateString(),
            'event_city' => 'Bogota',
            'event_notes' => 'Solicitud inicial de prueba',
            'last_message_at' => now(),
        ]);

        QuoteMessage::query()->create([
            'quote_conversation_id' => $conversation->id,
            'sender_user_id' => $client->id,
            'message' => 'Hola, quisiera una serenata para aniversario.',
            'is_initial' => true,
            'read_by_client_at' => now(),
        ]);

        QuoteMessage::query()->create([
            'quote_conversation_id' => $conversation->id,
            'sender_user_id' => $mariachiUser->id,
            'message' => 'Claro, tenemos disponibilidad para esa fecha.',
            'read_by_mariachi_at' => now(),
        ]);

        return [$client, $mariachiUser, $profile, $conversation];
    }
}

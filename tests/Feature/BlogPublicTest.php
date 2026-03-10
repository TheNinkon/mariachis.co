<?php

namespace Tests\Feature;

use App\Models\BlogCity;
use App\Models\BlogPost;
use App\Models\EventType;
use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_blog_index_shows_published_posts(): void
    {
        BlogPost::query()->create([
            'title' => 'Guia de mariachis en Bogota',
            'slug' => 'guia-mariachis-bogota',
            'status' => BlogPost::STATUS_PUBLISHED,
            'excerpt' => 'Contenido para bogota.',
            'published_at' => now(),
            'city_name' => 'Bogota',
        ]);

        BlogPost::query()->create([
            'title' => 'Borrador oculto',
            'slug' => 'borrador-oculto',
            'status' => BlogPost::STATUS_DRAFT,
        ]);

        $response = $this->get('/blog');

        $response->assertOk();
        $response->assertSee('Guia de mariachis en Bogota');
        $response->assertDontSee('Borrador oculto');
    }

    public function test_public_blog_show_works_with_slug(): void
    {
        $post = BlogPost::query()->create([
            'title' => 'Checklist serenata sorpresa',
            'slug' => 'checklist-serenata-sorpresa',
            'status' => BlogPost::STATUS_PUBLISHED,
            'content' => '<p>Contenido de prueba.</p>',
            'published_at' => now(),
        ]);

        $response = $this->get('/blog/'.$post->slug);

        $response->assertOk();
        $response->assertSee('Checklist serenata sorpresa');
        $response->assertSee('Contenido de prueba', false);
    }

    public function test_city_landing_shows_related_blog_posts(): void
    {
        $this->createPublishedListing('Bogota', 'mariachi-blog-bogota');

        $eventType = EventType::query()->create([
            'name' => 'Bodas',
            'is_active' => true,
        ]);

        $city = BlogCity::query()->create([
            'name' => 'Bogota',
            'slug' => 'bogota',
        ]);

        $post = BlogPost::query()->create([
            'title' => 'Consejos de bodas en Bogota',
            'slug' => 'consejos-bodas-bogota',
            'status' => BlogPost::STATUS_PUBLISHED,
            'city_name' => 'Bogota',
            'event_type_id' => $eventType->id,
            'published_at' => now(),
        ]);
        $post->cities()->sync([$city->id]);
        $post->eventTypes()->sync([$eventType->id]);

        $response = $this->get('/mariachis/bogota');

        $response->assertOk();
        $response->assertSee('Consejos de bodas en Bogota');
    }

    private function createPublishedListing(string $cityName, string $listingSlug): MariachiListing
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);

        $profile = MariachiProfile::query()->create([
            'user_id' => $user->id,
            'city_name' => $cityName,
            'business_name' => 'Mariachi '.$cityName,
            'profile_completed' => true,
            'profile_completion' => 100,
            'stage_status' => 'profile_complete',
        ]);

        return MariachiListing::query()->create([
            'mariachi_profile_id' => $profile->id,
            'slug' => $listingSlug,
            'title' => 'Listado '.$cityName,
            'city_name' => $cityName,
            'status' => MariachiListing::STATUS_ACTIVE,
            'is_active' => true,
            'listing_completed' => true,
            'listing_completion' => 100,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\User;
use App\Services\Seo\SeoPageCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_indexable_meta_tags(): void
    {
        app(SeoPageCatalog::class)->syncDefaults();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="index,follow" />', false);
        $response->assertSee('<link rel="canonical" href="http://localhost" />', false);
        $response->assertSee('<meta property="og:title"', false);
        $response->assertSee('<meta name="twitter:card" content="summary_large_image" />', false);
    }

    public function test_login_page_is_noindex(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="noindex,nofollow" />', false);
    }

    public function test_blog_post_prefers_explicit_seo_fields(): void
    {
        $post = BlogPost::query()->create([
            'title' => 'Checklist serenata sorpresa',
            'slug' => 'checklist-serenata-sorpresa',
            'meta_title' => 'Meta title serenata',
            'meta_description' => 'Meta description serenata',
            'canonical_override' => 'https://example.com/serenata-seo',
            'robots' => 'noindex,follow',
            'status' => BlogPost::STATUS_PUBLISHED,
            'content' => '<p>Contenido de prueba.</p>',
            'published_at' => now(),
        ]);

        $response = $this->get('/blog/'.$post->slug);

        $response->assertOk();
        $response->assertSee('<title>Meta title serenata | Mariachis.co</title>', false);
        $response->assertSee('<meta name="description" content="Meta description serenata" />', false);
        $response->assertSee('<meta name="robots" content="noindex,follow" />', false);
        $response->assertSee('<link rel="canonical" href="https://example.com/serenata-seo" />', false);
    }

    public function test_sitemap_and_robots_include_public_resources(): void
    {
        app(SeoPageCatalog::class)->syncDefaults();

        $user = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);

        $profile = MariachiProfile::query()->create([
            'user_id' => $user->id,
            'city_name' => 'Bogota',
            'business_name' => 'Mariachi Sitemap',
            'profile_completed' => true,
            'profile_completion' => 100,
            'stage_status' => 'profile_complete',
        ]);
        $profile->ensureSlug();
        $profile->save();

        MariachiListing::query()->create([
            'mariachi_profile_id' => $profile->id,
            'slug' => 'mariachi-sitemap-bogota',
            'title' => 'Mariachi Sitemap Bogota',
            'city_name' => 'Bogota',
            'status' => MariachiListing::STATUS_ACTIVE,
            'review_status' => MariachiListing::REVIEW_APPROVED,
            'is_active' => true,
            'listing_completed' => true,
            'listing_completion' => 100,
        ]);

        $robots = $this->get('/robots.txt');
        $robots->assertOk();
        $robots->assertSee('Disallow: /admin');
        $robots->assertSee('Sitemap: http://localhost/sitemap.xml');

        $sitemap = $this->get('/sitemap.xml');
        $sitemap->assertOk();
        $sitemap->assertSee('http://localhost/@'.$profile->slug);
        $sitemap->assertSee('http://localhost/mariachi/mariachi-sitemap-bogota');
        $sitemap->assertSee('http://localhost/terminos');
    }
}

<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\SeoPage;
use App\Models\User;
use App\Services\Seo\SeoPageCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_get_canonical_suggestion_for_seo_page(): void
    {
        app(SeoPageCatalog::class)->syncDefaults();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.seo-tools.canonical'), [
            'type' => 'page',
            'raw_context' => [
                'page_key' => 'home',
                'path' => '/',
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('canonical', 'http://localhost/');
    }

    public function test_admin_can_generate_jsonld_template_for_blog_index_page(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.seo-tools.jsonld'), [
            'type' => 'page',
            'raw_context' => [
                'page_key' => 'blog_index',
                'path' => '/blog',
                'title' => 'Blog y recursos para contratar mariachis',
                'description' => 'Consejos y recursos locales para contratar mariachis.',
            ],
        ]);

        $response->assertOk();
        $json = (string) $response->json('jsonld');
        $this->assertStringContainsString('CollectionPage', $json);
        $this->assertStringContainsString('BreadcrumbList', $json);
    }

    public function test_admin_can_clean_query_string_when_suggesting_canonical(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.seo-tools.canonical'), [
            'type' => 'page',
            'raw_context' => [
                'path' => '/blog?utm_source=ads&ref=campaign',
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('canonical', 'http://localhost/blog');
    }

    public function test_admin_can_generate_article_jsonld_template_for_blog_post(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.seo-tools.jsonld'), [
            'type' => 'blog_post',
            'raw_context' => [
                'title' => 'Como organizar una serenata sorpresa',
                'excerpt' => 'Checklist corto para preparar una serenata sin improvisar detalles clave.',
                'slug' => 'como-organizar-una-serenata-sorpresa',
                'city_name' => ['Bogota'],
                'primary_event_type' => ['Cumpleanos'],
                'headings' => ['Checklist previo', 'Logistica del evento'],
            ],
        ]);

        $response->assertOk();
        $json = (string) $response->json('jsonld');
        $this->assertStringContainsString('Article', $json);
        $this->assertStringContainsString('Bogota', $json);
        $this->assertStringContainsString('Logistica del evento', $json);
    }

    public function test_seo_page_update_rejects_invalid_jsonld(): void
    {
        app(SeoPageCatalog::class)->syncDefaults();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $page = SeoPage::query()->where('key', 'home')->firstOrFail();

        $response = $this->actingAs($admin)->from(route('admin.seo-pages.edit', $page))->put(route('admin.seo-pages.update', $page), [
            'title' => 'Home SEO',
            'meta_description' => 'Descripcion valida para home.',
            'keywords_target' => 'mariachis bogota',
            'robots' => 'index,follow',
            'canonical_override' => 'http://localhost/',
            'jsonld' => '{"@context":"https://schema.org"',
        ]);

        $response->assertRedirect(route('admin.seo-pages.edit', $page));
        $response->assertSessionHasErrors('jsonld');
    }

    public function test_blog_post_update_rejects_invalid_jsonld(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $post = BlogPost::query()->create([
            'title' => 'Post de prueba',
            'slug' => 'post-de-prueba',
            'status' => BlogPost::STATUS_DRAFT,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.blog-posts.edit', $post))
            ->put(route('admin.blog-posts.update', $post), [
                'title' => 'Post de prueba',
                'slug' => 'post-de-prueba',
                'status' => BlogPost::STATUS_DRAFT,
                'jsonld' => '{"@context":"https://schema.org"',
            ]);

        $response->assertRedirect(route('admin.blog-posts.edit', $post));
        $response->assertSessionHasErrors('jsonld');
    }
}

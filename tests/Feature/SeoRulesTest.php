<?php

namespace Tests\Feature;

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
}

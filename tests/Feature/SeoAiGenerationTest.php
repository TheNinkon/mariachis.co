<?php

namespace Tests\Feature;

use App\Models\SeoPage;
use App\Models\User;
use App\Services\Seo\SeoPageCatalog;
use App\Services\Seo\SeoSettingsService;
use App\Services\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SeoAiGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_gemini_ai_settings(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.seo-ai.update'), [
            'seo_gemini_api_key' => 'gemini-secret-key',
            'seo_gemini_model' => 'gemini-2.5-flash',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertSame('gemini-secret-key', app(SeoSettingsService::class)->geminiApiKey());
        $this->assertSame('gemini-2.5-flash', app(SeoSettingsService::class)->geminiModel());
        $this->assertDatabaseHas('system_settings', [
            'key' => SeoSettingsService::KEY_GEMINI_MODEL,
            'value' => 'gemini-2.5-flash',
            'is_encrypted' => false,
        ]);
        $this->assertDatabaseHas('system_settings', [
            'key' => SeoSettingsService::KEY_GEMINI_API_KEY,
            'is_encrypted' => true,
        ]);
    }

    public function test_admin_can_test_gemini_connection(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'meta_title' => 'Mariachis en Bogota',
                                'meta_description' => 'Encuentra mariachis en Bogota con informacion clara, cobertura local y detalles utiles para elegir el grupo ideal para tu evento.',
                                'keywords' => ['mariachis bogota', 'serenatas bogota'],
                                'og_title' => 'Mariachis en Bogota',
                                'og_description' => 'Encuentra mariachis en Bogota con informacion clara, cobertura local y detalles utiles para elegir el grupo ideal para tu evento.',
                            ], JSON_UNESCAPED_UNICODE),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.seo-ai.test'), [
            'seo_gemini_api_key' => 'gemini-secret-key',
            'seo_gemini_model' => 'gemini-2.5-flash',
        ]);

        $response->assertOk();
        $response->assertJson([
            'ok' => true,
            'model' => 'gemini-2.5-flash',
        ]);
    }

    public function test_admin_can_generate_seo_copy_for_page(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'meta_title' => 'Mariachis.co en Bogota',
                                'meta_description' => 'Descubre Mariachis.co en Bogota con enfoque local, informacion clara y detalles utiles para elegir mariachis para serenatas, bodas y eventos.',
                                'keywords' => ['mariachis bogota', 'serenatas', 'bodas'],
                                'og_title' => 'Mariachis.co en Bogota',
                                'og_description' => 'Descubre Mariachis.co en Bogota con enfoque local, informacion clara y detalles utiles para elegir mariachis para serenatas, bodas y eventos.',
                            ], JSON_UNESCAPED_UNICODE),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        app(SeoPageCatalog::class)->syncDefaults();
        app(SystemSettingService::class)->putString(SeoSettingsService::KEY_GEMINI_API_KEY, 'gemini-secret-key', true);
        app(SystemSettingService::class)->putString(SeoSettingsService::KEY_GEMINI_MODEL, 'gemini-2.5-flash');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $page = SeoPage::query()->where('key', 'home')->firstOrFail();

        $response = $this->actingAs($admin)->postJson(route('admin.seo-ai.generate'), [
            'type' => 'page',
            'language' => 'es',
            'keywords_target' => 'mariachis en bogota',
            'raw_context' => [
                'page_key' => $page->key,
                'page_label' => 'Home',
                'path' => '/',
                'title' => 'Mariachis.co',
                'meta_description' => 'Marketplace de mariachis.',
                'city_name' => 'Bogota',
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('model', 'gemini-2.5-flash');
        $response->assertJsonPath('meta_title', 'Mariachis.co en Bogota');
        $response->assertJsonStructure([
            'meta_title',
            'meta_description',
            'keywords',
            'og_title',
            'og_description',
            'model',
        ]);
    }

    public function test_admin_can_generate_seo_copy_for_blog_post(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'meta_title' => 'Checklist de serenata en Bogota',
                                'meta_description' => 'Prepara una serenata en Bogota con una guia clara, pasos practicos y contexto local para coordinar musica, tiempos y detalles sin improvisar.',
                                'keywords' => 'serenata en bogota, checklist serenata, sorpresa con mariachi, musica para cumpleanos, mariachis bogota, guia de serenata, planeacion de serenata, serenata sorpresa',
                                'og_title' => 'Checklist de serenata en Bogota',
                                'og_description' => 'Prepara una serenata en Bogota con una guia clara, pasos practicos y contexto local para coordinar musica, tiempos y detalles sin improvisar.',
                            ], JSON_UNESCAPED_UNICODE),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        app(SystemSettingService::class)->putString(SeoSettingsService::KEY_GEMINI_API_KEY, 'gemini-secret-key', true);
        app(SystemSettingService::class)->putString(SeoSettingsService::KEY_GEMINI_MODEL, 'gemini-2.5-flash');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.seo-ai.generate'), [
            'type' => 'post',
            'language' => 'es',
            'keywords_target' => 'serenata en bogota, checklist serenata',
            'raw_context' => [
                'title' => 'Como organizar una serenata sorpresa',
                'excerpt' => 'Checklist practico para una serenata sorpresa.',
                'headings' => ['Checklist previo', 'Logistica del evento'],
                'city_name' => ['Bogota'],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('meta_title', 'Checklist de serenata en Bogota');
        $response->assertJsonCount(8, 'keywords');
    }
}

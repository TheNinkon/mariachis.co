<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Seo\GeminiSeoGenerator;
use App\Services\Seo\SeoSettingsService;
use App\Services\SystemSettingService;
use App\Support\Admin\AdminAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class SeoAiController extends Controller
{
    public function __construct(
        private readonly SeoSettingsService $seoSettings,
        private readonly SystemSettingService $settings,
        private readonly GeminiSeoGenerator $generator,
        private readonly AdminAuditLogger $auditLogger
    ) {
    }

    public function edit(): View
    {
        return view('content.admin.seo-ai-settings', [
            'seo' => $this->seoSettings->adminConfig(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $models = array_keys($this->seoSettings->geminiModelOptions());

        $validated = $request->validate([
            'seo_gemini_api_key' => ['nullable', 'string', 'max:4096'],
            'clear_seo_gemini_api_key' => ['nullable', 'boolean'],
            'seo_gemini_model' => ['required', Rule::in($models)],
        ]);

        $this->settings->putString(SeoSettingsService::KEY_GEMINI_MODEL, $validated['seo_gemini_model']);

        if ($request->boolean('clear_seo_gemini_api_key')) {
            $this->settings->putString(SeoSettingsService::KEY_GEMINI_API_KEY, null, true);
        } elseif (filled($validated['seo_gemini_api_key'] ?? null)) {
            $this->settings->putString(SeoSettingsService::KEY_GEMINI_API_KEY, $validated['seo_gemini_api_key'], true);
        }

        $this->auditLogger->log($request, 'seo.ai.updated', [
            'model' => $validated['seo_gemini_model'],
            'gemini_key_rotated' => $request->boolean('clear_seo_gemini_api_key') || filled($validated['seo_gemini_api_key'] ?? null),
        ]);

        return back()->with('status', 'Configuración de IA SEO actualizada.');
    }

    public function testConnection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'seo_gemini_api_key' => ['nullable', 'string', 'max:4096'],
            'seo_gemini_model' => ['nullable', Rule::in(array_keys($this->seoSettings->geminiModelOptions()))],
        ]);

        try {
            return response()->json($this->generator->testConnection(
                $validated['seo_gemini_api_key'] ?? null,
                $validated['seo_gemini_model'] ?? null
            ));
        } catch (RuntimeException $exception) {
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['page', 'post', 'landing_template', 'global_settings'])],
            'language' => ['required', 'string', 'max:10'],
            'keywords_target' => ['nullable', 'string', 'max:255'],
            'raw_context' => ['required', 'array'],
        ]);

        try {
            return response()->json($this->generator->generateMeta($validated));
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}

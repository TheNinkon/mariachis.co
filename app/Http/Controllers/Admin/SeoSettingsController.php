<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Seo\SeoSettingsService;
use App\Services\SystemSettingService;
use App\Support\Admin\AdminAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SeoSettingsController extends Controller
{
    public function __construct(
        private readonly SeoSettingsService $seoSettings,
        private readonly SystemSettingService $settings,
        private readonly AdminAuditLogger $auditLogger
    ) {
    }

    public function edit(): View
    {
        return view('content.admin.seo-settings', [
            'seo' => $this->seoSettings->adminConfig(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'seo_site_name' => ['required', 'string', 'max:120'],
            'seo_default_title_template' => ['required', 'string', 'max:255'],
            'seo_default_meta_description' => ['required', 'string', 'max:320'],
            'seo_default_robots' => ['required', Rule::in(['index,follow', 'noindex,follow', 'noindex,nofollow'])],
            'seo_twitter_site' => ['nullable', 'string', 'max:120'],
            'seo_default_og_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'clear_seo_default_og_image' => ['nullable', 'boolean'],
            'seo_gemini_api_key' => ['nullable', 'string', 'max:4096'],
            'clear_seo_gemini_api_key' => ['nullable', 'boolean'],
        ]);

        $this->settings->putString(SeoSettingsService::KEY_SITE_NAME, $validated['seo_site_name']);
        $this->settings->putString(SeoSettingsService::KEY_DEFAULT_TITLE_TEMPLATE, $validated['seo_default_title_template']);
        $this->settings->putString(SeoSettingsService::KEY_DEFAULT_META_DESCRIPTION, $validated['seo_default_meta_description']);
        $this->settings->putString(SeoSettingsService::KEY_DEFAULT_ROBOTS, $validated['seo_default_robots']);
        $this->settings->putString(SeoSettingsService::KEY_TWITTER_SITE, $validated['seo_twitter_site'] ?? null);

        $currentOgImage = $this->settings->getString(SeoSettingsService::KEY_DEFAULT_OG_IMAGE_PATH);
        if ($request->boolean('clear_seo_default_og_image')) {
            if ($currentOgImage) {
                Storage::disk('public')->delete($currentOgImage);
            }

            $this->settings->putString(SeoSettingsService::KEY_DEFAULT_OG_IMAGE_PATH, null);
        } elseif ($request->hasFile('seo_default_og_image')) {
            if ($currentOgImage) {
                Storage::disk('public')->delete($currentOgImage);
            }

            $this->settings->putString(
                SeoSettingsService::KEY_DEFAULT_OG_IMAGE_PATH,
                $request->file('seo_default_og_image')->store('seo/defaults', 'public')
            );
        }

        if ($request->boolean('clear_seo_gemini_api_key')) {
            $this->settings->putString(SeoSettingsService::KEY_GEMINI_API_KEY, null, true);
        } elseif (filled($validated['seo_gemini_api_key'] ?? null)) {
            $this->settings->putString(SeoSettingsService::KEY_GEMINI_API_KEY, $validated['seo_gemini_api_key'], true);
        }

        $this->auditLogger->log($request, 'seo.settings.updated', [
            'site_name' => $validated['seo_site_name'],
            'default_robots' => $validated['seo_default_robots'],
            'twitter_site_present' => filled($validated['seo_twitter_site'] ?? null),
            'gemini_key_rotated' => $request->boolean('clear_seo_gemini_api_key') || filled($validated['seo_gemini_api_key'] ?? null),
        ]);

        return back()->with('status', 'Configuración SEO actualizada.');
    }
}

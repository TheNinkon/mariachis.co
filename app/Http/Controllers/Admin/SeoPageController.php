<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoPage;
use App\Services\Seo\SeoPageCatalog;
use App\Services\Seo\SeoRuleAssistantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SeoPageController extends Controller
{
    public function __construct(
        private readonly SeoPageCatalog $catalog,
        private readonly SeoRuleAssistantService $seoRules
    ) {
    }

    public function index(): View
    {
        $definitions = $this->catalog->definitions();
        $pages = $this->catalog->pages();

        return view('content.admin.seo-pages-index', [
            'pages' => $pages,
            'definitions' => $definitions,
        ]);
    }

    public function edit(SeoPage $seoPage): View
    {
        $this->catalog->syncDefaults();

        return view('content.admin.seo-pages-form', [
            'page' => $seoPage,
            'definition' => $this->catalog->definition($seoPage->key),
            'robotsOptions' => $this->robotsOptions(),
        ]);
    }

    public function update(Request $request, SeoPage $seoPage): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'keywords_target' => ['nullable', 'string', 'max:255'],
            'robots' => ['nullable', Rule::in(['index,follow', 'noindex,follow', 'noindex,nofollow'])],
            'canonical_override' => ['nullable', 'url:http,https', 'max:2048'],
            'jsonld' => ['nullable', 'string'],
            'og_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'clear_og_image' => ['nullable', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            if (! $this->seoRules->isValidJson($request->string('jsonld')->toString())) {
                $validator->errors()->add('jsonld', 'El JSON-LD debe ser un JSON válido.');
            }
        });

        $validated = $validator->validate();

        if ($request->boolean('clear_og_image')) {
            if ($seoPage->og_image) {
                Storage::disk('public')->delete($seoPage->og_image);
            }

            $seoPage->og_image = null;
        } elseif ($request->hasFile('og_image')) {
            if ($seoPage->og_image) {
                Storage::disk('public')->delete($seoPage->og_image);
            }

            $seoPage->og_image = $request->file('og_image')->store('seo/pages', 'public');
        }

        $seoPage->fill([
            'title' => ($validated['title'] ?? null) ?: null,
            'meta_description' => ($validated['meta_description'] ?? null) ?: null,
            'keywords_target' => ($validated['keywords_target'] ?? null) ?: null,
            'robots' => ($validated['robots'] ?? null) ?: null,
            'canonical_override' => ($validated['canonical_override'] ?? null) ?: null,
            'jsonld' => ($validated['jsonld'] ?? null) ?: null,
        ]);
        $seoPage->save();

        return redirect()->route('admin.seo-pages.index')->with('status', 'Página SEO actualizada.');
    }

    /**
     * @return array<string, string>
     */
    private function robotsOptions(): array
    {
        return [
            'index,follow' => 'index,follow',
            'noindex,follow' => 'noindex,follow',
            'noindex,nofollow' => 'noindex,nofollow',
        ];
    }
}

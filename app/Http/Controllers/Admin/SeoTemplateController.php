<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoTemplate;
use App\Services\Seo\SeoTemplateCatalog;
use App\Services\Seo\SeoTemplateRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SeoTemplateController extends Controller
{
    public function __construct(
        private readonly SeoTemplateCatalog $catalog,
        private readonly SeoTemplateRenderer $renderer
    ) {
    }

    public function index(): View
    {
        return view('content.admin.seo-templates-index', [
            'templates' => $this->catalog->templates(),
            'definitions' => $this->catalog->definitions(),
        ]);
    }

    public function edit(SeoTemplate $seoTemplate): View
    {
        $this->catalog->syncDefaults();

        abort_unless($this->catalog->definition($seoTemplate->template_key) !== null, 404);

        return view('content.admin.seo-templates-form', [
            'template' => $seoTemplate,
            'definition' => $this->catalog->definition($seoTemplate->template_key),
            'robotsOptions' => $this->robotsOptions(),
        ]);
    }

    public function update(Request $request, SeoTemplate $seoTemplate): RedirectResponse
    {
        $definition = $this->catalog->definition($seoTemplate->template_key);
        abort_unless($definition !== null, 404);

        $validator = Validator::make($request->all(), [
            'title_template' => ['required', 'string', 'max:500'],
            'description_template' => ['required', 'string', 'max:2000'],
            'robots' => ['required', Rule::in(array_keys($this->robotsOptions()))],
            'keywords_target' => ['nullable', 'string', 'max:255'],
            'og_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'clear_og_image' => ['nullable', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request, $definition): void {
            foreach (['title_template', 'description_template'] as $field) {
                $unknown = $this->renderer->unknownPlaceholders(
                    $request->string($field)->toString(),
                    $definition['placeholders']
                );

                if ($unknown !== []) {
                    $validator->errors()->add(
                        $field,
                        'Placeholder(s) no permitido(s): '.implode(', ', $unknown).'.'
                    );
                }
            }
        });

        $validated = $validator->validate();

        if ($request->boolean('clear_og_image')) {
            if ($seoTemplate->og_image_path) {
                Storage::disk('public')->delete($seoTemplate->og_image_path);
            }

            $seoTemplate->og_image_path = null;
        } elseif ($request->hasFile('og_image')) {
            if ($seoTemplate->og_image_path) {
                Storage::disk('public')->delete($seoTemplate->og_image_path);
            }

            $seoTemplate->og_image_path = $request->file('og_image')->store('seo/templates', 'public');
        }

        $seoTemplate->fill([
            'title_template' => $validated['title_template'],
            'description_template' => $validated['description_template'],
            'robots' => $validated['robots'],
            'keywords_target' => ($validated['keywords_target'] ?? null) ?: null,
        ]);
        $seoTemplate->save();

        return redirect()
            ->route('admin.seo-templates.index')
            ->with('status', 'Plantilla SEO actualizada.');
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

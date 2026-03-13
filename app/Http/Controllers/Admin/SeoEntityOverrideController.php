<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoEntityOverride;
use App\Services\Seo\GeminiSeoGenerator;
use App\Services\Seo\SeoDynamicEntityService;
use App\Services\Seo\SeoRuleAssistantService;
use App\Services\Seo\SeoTemplateCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class SeoEntityOverrideController extends Controller
{
    public function __construct(
        private readonly SeoDynamicEntityService $entities,
        private readonly SeoTemplateCatalog $templates,
        private readonly SeoRuleAssistantService $seoRules,
        private readonly GeminiSeoGenerator $generator
    ) {
    }

    public function edit(string $entityType, int $entityId): View
    {
        $entity = $this->entities->findEntity($entityType, $entityId);
        $meta = $this->entities->entityMeta($entityType);
        $context = $this->entities->buildContextForEntity($entityType, $entity);
        $override = $this->entities->override($entityType, $entityId) ?: new SeoEntityOverride([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);
        $template = $this->templates->template($meta['template_key']);

        return view('content.admin.seo-entity-overrides-form', [
            'entityType' => $entityType,
            'entityId' => $entityId,
            'entity' => $entity,
            'meta' => $meta,
            'context' => $context,
            'template' => $template,
            'override' => $override,
            'publicUrl' => $this->entities->publicUrl($entityType, $entity),
            'robotsOptions' => $this->robotsOptions(),
            'jsonldRuleType' => $this->jsonLdRuleType($entityType),
            'canonicalRuleType' => $this->canonicalRuleType($entityType),
        ]);
    }

    public function update(Request $request, string $entityType, int $entityId): RedirectResponse
    {
        $entity = $this->entities->findEntity($entityType, $entityId);

        $validator = Validator::make($request->all(), [
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'keywords_target' => ['nullable', 'string', 'max:255'],
            'robots' => ['nullable', Rule::in(array_keys($this->robotsOptions()))],
            'canonical_override' => ['nullable', 'url:http,https', 'max:2048'],
            'jsonld_override' => ['nullable', 'string'],
            'og_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'clear_og_image' => ['nullable', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            if (! $this->seoRules->isValidJson($request->string('jsonld_override')->toString())) {
                $validator->errors()->add('jsonld_override', 'El JSON-LD debe ser un JSON válido.');
            }
        });

        $validated = $validator->validate();

        $override = SeoEntityOverride::query()->firstOrNew([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);

        if ($request->boolean('clear_og_image')) {
            if ($override->og_image_path) {
                Storage::disk('public')->delete($override->og_image_path);
            }

            $override->og_image_path = null;
        } elseif ($request->hasFile('og_image')) {
            if ($override->og_image_path) {
                Storage::disk('public')->delete($override->og_image_path);
            }

            $override->og_image_path = $request->file('og_image')->store('seo/entity-overrides', 'public');
        }

        $override->fill([
            'meta_title' => ($validated['meta_title'] ?? null) ?: null,
            'meta_description' => ($validated['meta_description'] ?? null) ?: null,
            'keywords_target' => ($validated['keywords_target'] ?? null) ?: null,
            'robots' => ($validated['robots'] ?? null) ?: null,
            'canonical_override' => ($validated['canonical_override'] ?? null) ?: null,
            'jsonld_override' => ($validated['jsonld_override'] ?? null) ?: null,
        ]);
        $override->save();

        return redirect()
            ->route('admin.seo-entity-overrides.edit', ['entityType' => $entityType, 'entityId' => $entityId])
            ->with('status', 'Override SEO actualizado para '.$this->entityLabel($entity).'.');
    }

    public function generateMissing(Request $request, string $entityType): RedirectResponse
    {
        abort_unless($this->entities->supportsBatch($entityType), 404);

        $meta = $this->entities->entityMeta($entityType);
        $modelClass = $meta['model'];
        $generated = 0;

        try {
            $modelClass::query()
                ->with($meta['with'] ?? [])
                ->orderBy('id')
                ->chunkById(50, function ($entities) use ($entityType, &$generated): void {
                    foreach ($entities as $entity) {
                        /** @var Model $entity */
                        $context = $this->entities->buildContextForEntity($entityType, $entity);
                        $override = SeoEntityOverride::query()->firstOrNew([
                            'entity_type' => $entityType,
                            'entity_id' => (int) $entity->getKey(),
                        ]);

                        $hasEmptyFields = blank($override->meta_title)
                            || blank($override->meta_description)
                            || blank($override->keywords_target);

                        if (! $hasEmptyFields) {
                            continue;
                        }

                        $payload = $this->generator->generateMeta([
                            'type' => 'entity_override',
                            'language' => 'es',
                            'keywords_target' => $override->keywords_target ?: '',
                            'raw_context' => array_merge($context, [
                                'entity_type' => $entityType,
                                'public_url' => $this->entities->publicUrl($entityType, $entity),
                            ]),
                        ]);

                        if (blank($override->meta_title)) {
                            $override->meta_title = $payload['meta_title'] ?? null;
                        }

                        if (blank($override->meta_description)) {
                            $override->meta_description = $payload['meta_description'] ?? null;
                        }

                        if (blank($override->keywords_target)) {
                            $override->keywords_target = collect($payload['keywords'] ?? [])
                                ->filter()
                                ->implode(', ') ?: null;
                        }

                        $override->save();
                        $generated++;
                    }
                });
        } catch (RuntimeException $exception) {
            return back()->withErrors([
                'seo_batch' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', 'SEO faltante generado para '.$generated.' elemento(s).');
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

    private function entityLabel(Model $entity): string
    {
        foreach (['name', 'title', 'business_name', 'slug'] as $field) {
            $value = trim((string) data_get($entity, $field, ''));
            if ($value !== '') {
                return $value;
            }
        }

        return class_basename($entity).' #'.$entity->getKey();
    }

    private function canonicalRuleType(string $entityType): string
    {
        return match ($entityType) {
            'listing' => 'listing',
            'profile' => 'profile',
            default => 'seo_landing',
        };
    }

    private function jsonLdRuleType(string $entityType): ?string
    {
        return match ($entityType) {
            'listing' => 'listing',
            'profile' => 'profile',
            'city', 'zone', 'event_type' => 'seo_landing',
            default => null,
        };
    }
}

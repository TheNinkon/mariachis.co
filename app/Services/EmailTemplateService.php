<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Support\EmailTemplates\EmailTemplateCatalog;
use Illuminate\Database\Eloquent\Collection;

class EmailTemplateService
{
    public function __construct(private readonly TemplateRenderer $renderer)
    {
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function catalog(): array
    {
        return EmailTemplateCatalog::definitions();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function catalogDefinition(string $key): ?array
    {
        return EmailTemplateCatalog::definition($key);
    }

    public function findEditable(string $key): ?EmailTemplate
    {
        $template = EmailTemplate::query()->where('key', $key)->first();

        if ($template) {
            return $template;
        }

        $definition = $this->catalogDefinition($key);

        if (! $definition) {
            return null;
        }

        return new EmailTemplate([
            'key' => $definition['key'],
            'name' => $definition['name'],
            'audience' => $definition['audience'],
            'description' => $definition['description'],
            'subject' => $definition['subject'],
            'body_html' => $definition['body_html'],
            'variables_schema' => $definition['variables_schema'],
            'is_active' => true,
        ]);
    }

    public function findActive(string $key): ?EmailTemplate
    {
        return EmailTemplate::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->first();
    }

    /**
     * @return Collection<int, EmailTemplate>
     */
    public function listForAdmin(): Collection
    {
        $stored = EmailTemplate::query()
            ->orderBy('audience')
            ->orderBy('name')
            ->get()
            ->keyBy('key');

        $templates = collect($this->catalog())
            ->map(function (array $definition, string $key) use ($stored): EmailTemplate {
                return $stored->get($key) ?? new EmailTemplate([
                    'key' => $definition['key'],
                    'name' => $definition['name'],
                    'audience' => $definition['audience'],
                    'description' => $definition['description'],
                    'subject' => $definition['subject'],
                    'body_html' => $definition['body_html'],
                    'variables_schema' => $definition['variables_schema'],
                    'is_active' => true,
                ]);
            })
            ->merge(
                $stored->filter(fn (EmailTemplate $template, string $key): bool => ! array_key_exists($key, $this->catalog()))
            )
            ->sortBy([
                ['audience', 'asc'],
                ['name', 'asc'],
            ])
            ->values();

        return new Collection($templates->all());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function save(string $key, array $payload, ?int $updatedByUserId): EmailTemplate
    {
        $definition = $this->catalogDefinition($key);

        if (! $definition) {
            abort(404);
        }

        return EmailTemplate::query()->updateOrCreate(
            ['key' => $key],
            [
                'name' => $definition['name'],
                'audience' => $definition['audience'],
                'description' => $definition['description'],
                'subject' => $payload['subject'],
                'body_html' => $payload['body_html'],
                'variables_schema' => $definition['variables_schema'],
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'updated_by' => $updatedByUserId,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array{subject:string,html:string}|null
     */
    public function renderActive(string $key, array $variables): ?array
    {
        $template = $this->findActive($key);

        if (! $template) {
            return null;
        }

        return $this->renderTemplate($template, $variables);
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array{subject:string,html:string}
     */
    public function renderEditable(EmailTemplate $template, array $variables): array
    {
        return $this->renderTemplate($template, $variables);
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array{subject:string,html:string}
     */
    public function renderDefinition(string $key, array $variables): array
    {
        $definition = $this->catalogDefinition($key);

        abort_if($definition === null, 404);

        return [
            'subject' => $this->renderer->renderText((string) $definition['subject'], $variables),
            'html' => $this->renderer->renderHtml((string) $definition['body_html'], $variables),
        ];
    }

    /**
     * @return list<string>
     */
    public function allowedVariables(EmailTemplate $template): array
    {
        return array_values(array_map(
            static fn (array $item): string => (string) $item['key'],
            $template->variables_schema ?? []
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function mockVariables(string $key): array
    {
        return $this->catalogDefinition($key)['mock_data'] ?? [];
    }

    /**
     * @param  list<string>  $allowedVariables
     * @return list<string>
     */
    public function invalidVariables(string $subject, string $bodyHtml, array $allowedVariables): array
    {
        return array_values(array_unique(array_merge(
            $this->renderer->unknownPlaceholders($subject, $allowedVariables),
            $this->renderer->unknownPlaceholders($bodyHtml, $allowedVariables),
        )));
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array{subject:string,html:string}
     */
    private function renderTemplate(EmailTemplate $template, array $variables): array
    {
        return [
            'subject' => $this->renderer->renderText((string) $template->subject, $variables),
            'html' => $this->renderer->renderHtml((string) $template->body_html, $variables),
        ];
    }
}

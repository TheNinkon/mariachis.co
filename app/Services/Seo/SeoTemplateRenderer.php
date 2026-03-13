<?php

namespace App\Services\Seo;

class SeoTemplateRenderer
{
    /**
     * @return list<string>
     */
    public function placeholders(string $template): array
    {
        preg_match_all('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', $template, $matches);

        return collect($matches[1] ?? [])
            ->map(fn (mixed $placeholder): string => trim((string) $placeholder))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $allowed
     * @return list<string>
     */
    public function unknownPlaceholders(string $template, array $allowed): array
    {
        return collect($this->placeholders($template))
            ->reject(fn (string $placeholder): bool => in_array($placeholder, $allowed, true))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, scalar|null>  $values
     */
    public function render(string $template, array $values): string
    {
        $rendered = preg_replace_callback('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', function (array $matches) use ($values): string {
            $key = trim((string) ($matches[1] ?? ''));
            $value = $values[$key] ?? '';

            return is_scalar($value) ? trim((string) $value) : '';
        }, $template) ?? $template;

        $rendered = preg_replace('/\s{2,}/u', ' ', $rendered) ?? $rendered;
        $rendered = preg_replace('/\s+([,.;:!?|])/u', '$1', $rendered) ?? $rendered;

        return trim($rendered);
    }
}

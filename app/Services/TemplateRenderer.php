<?php

namespace App\Services;

class TemplateRenderer
{
    /**
     * @return list<string>
     */
    public function extractPlaceholders(string $content): array
    {
        preg_match_all('/{{\s*([a-zA-Z0-9_]+)\s*}}/', $content, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * @param  list<string>  $allowedKeys
     * @return list<string>
     */
    public function unknownPlaceholders(string $content, array $allowedKeys): array
    {
        $allowed = array_fill_keys($allowedKeys, true);

        return array_values(array_filter(
            $this->extractPlaceholders($content),
            static fn (string $key): bool => ! isset($allowed[$key])
        ));
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    public function renderHtml(string $content, array $variables): string
    {
        return preg_replace_callback(
            '/{{\s*([a-zA-Z0-9_]+)\s*}}/',
            function (array $matches) use ($variables): string {
                $value = $variables[$matches[1]] ?? '';

                return htmlspecialchars($this->stringify($value), ENT_QUOTES, 'UTF-8');
            },
            $content
        ) ?? $content;
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    public function renderText(string $content, array $variables): string
    {
        return preg_replace_callback(
            '/{{\s*([a-zA-Z0-9_]+)\s*}}/',
            fn (array $matches): string => $this->stringify($variables[$matches[1]] ?? ''),
            $content
        ) ?? $content;
    }

    private function stringify(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value) || $value === null) {
            return trim((string) $value);
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}

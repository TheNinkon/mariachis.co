<?php

namespace App\Services\Seo;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GeminiSeoGenerator
{
    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct(private readonly SeoSettingsService $settings)
    {
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{
     *   meta_title:string,
     *   meta_description:string,
     *   keywords:list<string>,
     *   og_title:string,
     *   og_description:string,
     *   title_template_suggestion:?string,
     *   twitter_site_suggestion:?string,
     *   model:string
     * }
     */
    public function generateMeta(array $context, ?string $apiKey = null, ?string $model = null): array
    {
        $apiKey = $this->resolveApiKey($apiKey);
        $model = $this->resolveModel($model);

        $response = Http::acceptJson()
            ->asJson()
            ->timeout(25)
            ->retry(1, 250)
            ->post(self::API_BASE.$model.':generateContent?key='.$apiKey, [
                'systemInstruction' => [
                    'parts' => [[
                        'text' => $this->systemPrompt(),
                    ]],
                ],
                'contents' => [[
                    'role' => 'user',
                    'parts' => [[
                        'text' => $this->buildPrompt($context),
                    ]],
                ]],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'responseMimeType' => 'application/json',
                ],
            ]);

        if (! $response->successful()) {
            $message = data_get($response->json(), 'error.message')
                ?: 'Gemini no pudo generar contenido SEO en este momento.';

            throw new RuntimeException($message);
        }

        $payload = $this->extractPayload($response->json());

        $metaTitle = $this->sanitizeTitle(Arr::get($payload, 'meta_title'), $context);
        $metaDescription = $this->sanitizeDescription(Arr::get($payload, 'meta_description'), $context);
        $ogTitle = $this->sanitizeTitle(Arr::get($payload, 'og_title') ?: $metaTitle, $context);
        $ogDescription = $this->sanitizeDescription(Arr::get($payload, 'og_description') ?: $metaDescription, $context);

        return [
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'keywords' => $this->sanitizeKeywords(Arr::get($payload, 'keywords')),
            'og_title' => $ogTitle,
            'og_description' => $ogDescription,
            'title_template_suggestion' => $this->sanitizeTitleTemplateSuggestion(Arr::get($payload, 'title_template_suggestion')),
            'twitter_site_suggestion' => $this->sanitizeTwitterSiteSuggestion(Arr::get($payload, 'twitter_site_suggestion')),
            'model' => $model,
        ];
    }

    /**
     * @return array{ok:bool, model:string, message:string}
     */
    public function testConnection(?string $apiKey = null, ?string $model = null): array
    {
        $result = $this->generateMeta([
            'type' => 'page',
            'language' => 'es',
            'keywords_target' => 'seo mariachis colombia',
            'raw_context' => [
                'title' => 'Mariachis.co',
                'label' => 'Home',
                'description' => 'Marketplace local para contratar mariachis en Colombia.',
            ],
        ], $apiKey, $model);

        return [
            'ok' => true,
            'model' => $result['model'],
            'message' => 'Conexión exitosa con '.$result['model'].'.',
        ];
    }

    /**
     * @param  mixed  $value
     * @return list<string>
     */
    private function sanitizeKeywords(mixed $value): array
    {
        $items = is_array($value)
            ? $value
            : preg_split('/,\s*/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);

        return collect($items)
            ->map(fn (mixed $item): string => $this->sanitizeText((string) $item))
            ->filter()
            ->take(8)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function sanitizeTitle(?string $value, array $context): string
    {
        $title = $this->sanitizeText($value);
        if ($title === '') {
            $title = $this->fallbackTitle($context);
        }

        return $this->truncateAtWordBoundary($title, 60);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function sanitizeDescription(?string $value, array $context): string
    {
        $description = $this->sanitizeText($value);
        if ($description === '') {
            $description = $this->fallbackDescription($context);
        }

        if (Str::length($description) > 160) {
            $description = $this->truncateAtWordBoundary($description, 160, true);
        }

        if (Str::length($description) < 140) {
            $description = $this->fallbackDescription($context, $description);
        }

        return $description;
    }

    private function sanitizeText(?string $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $normalized = strip_tags($value);
        $normalized = str_replace(
            ["\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}", "\u{00AB}", "\u{00BB}"],
            ["'", "'", '"', '"', '"', '"'],
            $normalized
        );
        $normalized = preg_replace('/[\x{2600}-\x{27BF}\x{1F000}-\x{1FAFF}]/u', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
        $normalized = trim($normalized, " \t\n\r\0\x0B\"'");
        $normalized = preg_replace('/\b(el mejor|la mejor|numero 1|número 1|garantizado(?:s|as)?|imperdible)\b/iu', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s{2,}/', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function fallbackTitle(array $context): string
    {
        $rawContext = Arr::get($context, 'raw_context', []);
        $type = (string) Arr::get($context, 'type', 'page');

        $title = $this->contextText($rawContext, 'title')
            ?: $this->contextText($rawContext, 'label')
            ?: $this->contextText($rawContext, 'business_name')
            ?: $this->contextText($rawContext, 'page_title')
            ?: ($type === 'post' ? 'Articulo de mariachis' : 'SEO Mariachis.co');

        $city = $this->contextText($rawContext, 'city_name');

        return $city
            ? $this->sanitizeText($title.' en '.$city)
            : $this->sanitizeText((string) $title);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function fallbackDescription(array $context, string $prefix = ''): string
    {
        $rawContext = Arr::get($context, 'raw_context', []);
        $type = (string) Arr::get($context, 'type', 'page');

        $subject = $this->contextText($rawContext, 'title')
            ?: $this->contextText($rawContext, 'label')
            ?: $this->contextText($rawContext, 'business_name')
            ?: ($type === 'post' ? 'este contenido' : 'esta pagina');

        $city = $this->contextText($rawContext, 'city_name');
        $event = $this->contextText($rawContext, 'primary_event_type');
        $base = trim($prefix);

        $parts = collect([
            $base !== '' ? $base : null,
            'Descubre '.$subject.($city ? ' en '.$city : '').' con informacion clara, enfoque local y detalles utiles para tomar una mejor decision.',
            $event ? 'Ideal para usuarios que buscan '.$event.' y servicios de mariachis en Colombia.' : 'Pensada para busquedas locales y consultas reales en Colombia.',
        ])->filter()->all();

        $description = $this->sanitizeText(implode(' ', $parts));

        if (Str::length($description) > 160) {
            return $this->truncateAtWordBoundary($description, 160, true);
        }

        if (Str::length($description) < 140) {
            $description = $this->sanitizeText($description.' Encuentra contexto, cobertura y datos clave antes de elegir.');
        }

        if (Str::length($description) > 160) {
            $description = $this->truncateAtWordBoundary($description, 160, true);
        }

        return $description;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function buildPrompt(array $context): string
    {
        return implode("\n\n", [
            'Genera metadatos SEO en espanol para Colombia.',
            'Entrega solo JSON valido. Llaves requeridas: meta_title, meta_description, keywords, og_title, og_description.',
            'Llaves opcionales: title_template_suggestion, twitter_site_suggestion.',
            'Reglas: meta_title y og_title maximo 60 caracteres; meta_description y og_description entre 140 y 160 caracteres; keywords como arreglo corto; sin emojis; sin claims falsos; no inventes datos faltantes.',
            'Si propones title_template_suggestion, conserva literalmente {{title}} y {{site_name}}.',
            'Contexto de entrada:',
            json_encode($this->promptContext($context), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '{}',
        ]);
    }

    private function systemPrompt(): string
    {
        return 'Eres un especialista SEO senior para marketplaces de mariachis en Colombia. '
            .'Escribes en espanol neutro con enfoque local, utilidad real y precision editorial.';
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function promptContext(array $context): array
    {
        $rawContext = Arr::get($context, 'raw_context', []);

        if (is_array($rawContext)) {
            $rawContext = collect($rawContext)->map(function (mixed $value) {
                if (is_array($value)) {
                    return collect($value)
                        ->map(fn (mixed $item): string => $this->truncateAtWordBoundary($this->sanitizeText((string) $item), 80))
                        ->filter()
                        ->values()
                        ->all();
                }

                return $this->truncateAtWordBoundary($this->sanitizeText((string) $value), 400);
            })->all();
        }

        return [
            'type' => Arr::get($context, 'type', 'page'),
            'language' => Arr::get($context, 'language', 'es'),
            'keywords_target' => $this->sanitizeText((string) Arr::get($context, 'keywords_target', '')),
            'raw_context' => $rawContext,
        ];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function extractPayload(array $response): array
    {
        $text = data_get($response, 'candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Gemini no devolvio contenido utilizable.');
        }

        $json = trim($text);
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            preg_match('/\{.*\}/s', $json, $matches);
            $decoded = isset($matches[0]) ? json_decode($matches[0], true) : null;
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini devolvio un formato inesperado.');
        }

        return $decoded;
    }

    private function resolveApiKey(?string $apiKey = null): string
    {
        $resolved = trim((string) ($apiKey ?: $this->settings->geminiApiKey()));

        if ($resolved === '') {
            throw new RuntimeException('Configura una GEMINI_API_KEY antes de generar contenido SEO.');
        }

        return $resolved;
    }

    private function resolveModel(?string $model = null): string
    {
        $resolved = trim((string) ($model ?: $this->settings->geminiModel()));
        $options = $this->settings->geminiModelOptions();

        if ($resolved === '' || ! array_key_exists($resolved, $options)) {
            return $this->settings->geminiModel();
        }

        return $resolved;
    }

    private function truncateAtWordBoundary(string $value, int $limit, bool $appendPeriod = false): string
    {
        $value = trim($value);

        if (Str::length($value) <= $limit) {
            return $value;
        }

        $truncated = Str::substr($value, 0, $limit);
        $lastSpace = mb_strrpos($truncated, ' ');

        if ($lastSpace !== false && $lastSpace > (int) floor($limit * 0.6)) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }

        $truncated = rtrim($truncated, " \t\n\r\0\x0B,.;:-");

        return $appendPeriod ? $truncated.'.' : $truncated;
    }

    private function sanitizeTitleTemplateSuggestion(mixed $value): ?string
    {
        $template = $this->sanitizeText((string) $value);

        if ($template === '') {
            return null;
        }

        if (! str_contains($template, '{{title}}') || ! str_contains($template, '{{site_name}}')) {
            return '{{title}} | {{site_name}}';
        }

        return Str::limit($template, 120, '');
    }

    private function sanitizeTwitterSiteSuggestion(mixed $value): ?string
    {
        $handle = trim((string) $value);

        if ($handle === '') {
            return null;
        }

        $handle = '@'.ltrim($handle, '@');
        $handle = preg_replace('/[^@a-zA-Z0-9_]/', '', $handle) ?? $handle;

        return strlen($handle) > 1 ? Str::limit($handle, 50, '') : null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function contextText(array $context, string $key): ?string
    {
        $value = Arr::get($context, $key);

        if (is_array($value)) {
            $value = collect($value)->filter()->implode(', ');
        }

        $text = $this->sanitizeText((string) $value);

        return $text !== '' ? $text : null;
    }
}

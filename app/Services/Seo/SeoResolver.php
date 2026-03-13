<?php

namespace App\Services\Seo;

use App\Models\SeoPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SeoResolver
{
    public function __construct(
        private readonly SeoSettingsService $settings,
        private readonly SeoPageCatalog $catalog,
        private readonly SeoRuleAssistantService $seoRules,
        private readonly SeoDynamicEntityService $dynamicEntities
    ) {
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function resolve(Request $request, ?string $pageType = null, array $context = []): array
    {
        $pageType ??= $this->inferPageType($request);
        $pageKey = (string) ($context['page_key'] ?? $this->inferPageKey($request, $pageType));
        $pageRecord = $pageKey !== '' ? $this->pageRecord($pageKey) : null;
        $pageDefinition = $pageKey !== '' ? ($this->catalog->definition($pageKey) ?? []) : [];
        $entitySeo = $pageRecord ? null : $this->dynamicEntities->resolve($pageType, $context, $request);
        $resolvedContext = array_merge($entitySeo['placeholder_values'] ?? [], $context);

        $baseTitle = trim((string) ($pageRecord?->title
            ?? ($entitySeo['title'] ?? null)
            ?? $context['title']
            ?? ($pageDefinition['title'] ?? '')));
        $siteName = $this->settings->siteName();

        if ($baseTitle === '') {
            $baseTitle = $siteName;
        }

        $title = $this->formatTitle($baseTitle, $siteName);
        $description = trim((string) ($pageRecord?->meta_description
            ?? ($entitySeo['description'] ?? null)
            ?? $context['description']
            ?? ($pageDefinition['meta_description'] ?? '')
            ?: $this->settings->defaultMetaDescription()));

        $canonical = trim((string) ($pageRecord?->canonical_override
            ?? ($entitySeo['canonical'] ?? null)
            ?? $context['canonical']
            ?? $this->absoluteUrl((string) ($pageDefinition['path'] ?? ''))
            ?? $request->url()));

        $robots = trim((string) ($pageRecord?->robots
            ?? ($entitySeo['robots'] ?? null)
            ?? $context['robots']
            ?? ($pageDefinition['robots'] ?? '')
            ?: $this->defaultRobotsForPageType($pageType, $request)));

        $ogImage = $this->normalizeImageUrl(
            $pageRecord?->og_image
                ?? ($entitySeo['og_image'] ?? null)
                ?? $context['og_image']
                ?? null
        ) ?: $this->settings->defaultOgImageUrl();

        $ogType = trim((string) ($context['og_type'] ?? $this->defaultOgType($pageType)));
        $jsonLd = $pageRecord?->jsonld ?? ($entitySeo['jsonld'] ?? null) ?? $context['jsonld'] ?? ($context['schema'] ?? null);
        $jsonLd = is_string($jsonLd) ? trim($jsonLd) : null;

        if ($jsonLd === null || $jsonLd === '') {
            $jsonLd = $this->seoRules->generateJsonLd(
                $this->jsonLdTypeForPage($pageType, $pageKey, $resolvedContext),
                array_merge($resolvedContext, [
                    'page_key' => $pageKey,
                    'title' => trim((string) ($resolvedContext['jsonld_title'] ?? $baseTitle)),
                    'description' => trim((string) ($resolvedContext['jsonld_description'] ?? $description)),
                    'canonical' => trim((string) ($resolvedContext['jsonld_canonical'] ?? $canonical)),
                ])
            );
        }

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $robots,
            'site_name' => $siteName,
            'og' => [
                'title' => trim((string) ($pageRecord?->title ?? $context['og_title'] ?? $title)),
                'description' => trim((string) ($pageRecord?->meta_description ?? $context['og_description'] ?? $description)),
                'image' => $ogImage,
                'url' => trim((string) ($context['og_url'] ?? $canonical)),
                'type' => $ogType,
            ],
            'twitter' => [
                'card' => trim((string) ($context['twitter_card'] ?? 'summary_large_image')),
                'title' => trim((string) ($context['twitter_title'] ?? $title)),
                'description' => trim((string) ($context['twitter_description'] ?? $description)),
                'image' => $this->normalizeImageUrl($context['twitter_image'] ?? null) ?: $ogImage,
                'site' => $this->settings->twitterSite(),
            ],
            'jsonld' => $jsonLd,
            'page_key' => $pageKey !== '' ? $pageKey : null,
            'page_type' => $pageType,
        ];
    }

    private function inferPageType(Request $request): string
    {
        $routeName = (string) $request->route()?->getName();

        return match (true) {
            $routeName === 'home' => 'home',
            $routeName === 'blog.index' => 'blog_index',
            $routeName === 'blog.show' => 'blog_post',
            Str::startsWith($routeName, 'seo.landing') => 'landing',
            $routeName === 'mariachi.public.show' => 'listing',
            $routeName === 'mariachi.provider.public.show' => 'profile',
            Str::startsWith($routeName, 'client.login'),
            Str::startsWith($routeName, 'client.password'),
            $routeName === 'client.register' => 'auth_page',
            Str::startsWith($routeName, 'client.account.'),
            Str::startsWith($routeName, 'client.dashboard') => 'private_page',
            Str::startsWith($routeName, 'public.collections.') => 'private_page',
            default => 'static_page',
        };
    }

    private function inferPageKey(Request $request, string $pageType): string
    {
        $routeName = (string) $request->route()?->getName();

        return match ($pageType) {
            'home' => 'home',
            'blog_index' => 'blog_index',
            'static_page' => match ($routeName) {
                'static.terms' => 'terms',
                'static.privacy' => 'privacy',
                'static.help' => 'help',
                default => '',
            },
            default => '',
        };
    }

    private function pageRecord(string $pageKey): ?SeoPage
    {
        $this->catalog->syncDefaults();

        return SeoPage::query()->where('key', $pageKey)->first();
    }

    private function formatTitle(string $title, string $siteName): string
    {
        if ($title === '' || mb_stripos($title, $siteName) !== false) {
            return $title !== '' ? $title : $siteName;
        }

        $template = $this->settings->titleTemplate();

        return str_replace(
            ['{{title}}', '{{site_name}}'],
            [$title, $siteName],
            $template
        );
    }

    private function defaultRobotsForPageType(string $pageType, Request $request): string
    {
        if (in_array($pageType, ['auth_page', 'private_page'], true)) {
            return 'noindex,nofollow';
        }

        $routeName = (string) $request->route()?->getName();
        if ($routeName === 'fallback.404') {
            return 'noindex,follow';
        }

        return $this->settings->defaultRobots();
    }

    private function defaultOgType(string $pageType): string
    {
        return match ($pageType) {
            'blog_post' => 'article',
            'profile' => 'profile',
            default => 'website',
        };
    }

    private function absoluteUrl(string $path): ?string
    {
        $normalized = trim($path);
        if ($normalized === '') {
            return null;
        }

        if (Str::startsWith($normalized, ['http://', 'https://'])) {
            return $normalized;
        }

        return url($normalized);
    }

    private function normalizeImageUrl(mixed $value): ?string
    {
        $image = trim((string) $value);
        if ($image === '') {
            return null;
        }

        if (Str::startsWith($image, ['http://', 'https://', 'data:'])) {
            return $image;
        }

        return asset('storage/'.$image);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function jsonLdTypeForPage(string $pageType, string $pageKey, array $context): string
    {
        return match ($pageType) {
            'home', 'blog_index', 'static_page' => 'page',
            'landing' => 'landing_template',
            'blog_post' => 'post',
            'listing' => 'listing',
            'profile' => 'profile',
            default => ($pageKey === 'help' && ! empty($context['faq_items'])) ? 'faq' : 'page',
        };
    }
}

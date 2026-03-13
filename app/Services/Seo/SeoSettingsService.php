<?php

namespace App\Services\Seo;

use App\Services\SystemSettingService;

class SeoSettingsService
{
    public const KEY_SITE_NAME = 'seo_site_name';
    public const KEY_DEFAULT_TITLE_TEMPLATE = 'seo_default_title_template';
    public const KEY_DEFAULT_META_DESCRIPTION = 'seo_default_meta_description';
    public const KEY_DEFAULT_OG_IMAGE_PATH = 'seo_default_og_image_path';
    public const KEY_DEFAULT_ROBOTS = 'seo_default_robots';
    public const KEY_TWITTER_SITE = 'seo_twitter_site';
    public const KEY_GEMINI_API_KEY = 'seo_gemini_api_key';

    public function __construct(private readonly SystemSettingService $settings)
    {
    }

    /**
     * @return array{
     *   site_name:string,
     *   default_title_template:string,
     *   default_meta_description:string,
     *   default_og_image_path:?string,
     *   default_og_image_url:?string,
     *   default_robots:string,
     *   twitter_site:?string,
     *   gemini_api_key_set:bool
     * }
     */
    public function adminConfig(): array
    {
        $imagePath = $this->settings->getString(self::KEY_DEFAULT_OG_IMAGE_PATH);

        return [
            'site_name' => $this->siteName(),
            'default_title_template' => $this->titleTemplate(),
            'default_meta_description' => $this->defaultMetaDescription(),
            'default_og_image_path' => $imagePath,
            'default_og_image_url' => $imagePath ? asset('storage/'.$imagePath) : asset('marketplace/assets/logo-wordmark.png'),
            'default_robots' => $this->defaultRobots(),
            'twitter_site' => $this->settings->getString(self::KEY_TWITTER_SITE),
            'gemini_api_key_set' => filled($this->settings->getString(self::KEY_GEMINI_API_KEY)),
        ];
    }

    public function siteName(): string
    {
        return $this->settings->getString(self::KEY_SITE_NAME, 'Mariachis.co') ?: 'Mariachis.co';
    }

    public function titleTemplate(): string
    {
        return $this->settings->getString(self::KEY_DEFAULT_TITLE_TEMPLATE, '{{title}} | {{site_name}}')
            ?: '{{title}} | {{site_name}}';
    }

    public function defaultMetaDescription(): string
    {
        return $this->settings->getString(
            self::KEY_DEFAULT_META_DESCRIPTION,
            'Marketplace local para contratar mariachis en Colombia.'
        ) ?: 'Marketplace local para contratar mariachis en Colombia.';
    }

    public function defaultOgImageUrl(): string
    {
        $path = $this->settings->getString(self::KEY_DEFAULT_OG_IMAGE_PATH);

        return $path ? asset('storage/'.$path) : asset('marketplace/assets/logo-wordmark.png');
    }

    public function defaultRobots(): string
    {
        return $this->settings->getString(self::KEY_DEFAULT_ROBOTS, 'index,follow') ?: 'index,follow';
    }

    public function twitterSite(): ?string
    {
        return $this->settings->getString(self::KEY_TWITTER_SITE);
    }
}

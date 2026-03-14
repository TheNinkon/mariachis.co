<?php

namespace App\Services;

use InvalidArgumentException;

class SocialLoginSettingsService
{
    public const PROVIDER_GOOGLE = 'google';
    public const PROVIDER_FACEBOOK = 'facebook';

    public const KEY_GOOGLE_ENABLED = 'social_login.google.enabled';
    public const KEY_GOOGLE_CLIENT_ID = 'social_login.google.client_id';
    public const KEY_GOOGLE_REDIRECT_URI = 'social_login.google.redirect_uri';

    public const KEY_FACEBOOK_ENABLED = 'social_login.facebook.enabled';
    public const KEY_FACEBOOK_CLIENT_ID = 'social_login.facebook.client_id';
    public const KEY_FACEBOOK_REDIRECT_URI = 'social_login.facebook.redirect_uri';

    /**
     * @var list<string>
     */
    private const SUPPORTED_PROVIDERS = [
        self::PROVIDER_GOOGLE,
        self::PROVIDER_FACEBOOK,
    ];

    /**
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $resolvedProviders = null;

    public function __construct(private readonly SystemSettingService $settings)
    {
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function publicConfig(): array
    {
        return $this->providers();
    }

    /**
     * @return array<string, mixed>
     */
    public function providerConfig(string $provider): array
    {
        return $this->providers()[$this->normalizeProvider($provider)];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function clientEnabledProviders(): array
    {
        return array_filter(
            $this->providers(),
            static fn (array $provider): bool => $provider['is_ready'] === true
        );
    }

    public function isReady(string $provider): bool
    {
        return $this->providerConfig($provider)['is_ready'] === true;
    }

    public function applyRuntimeConfig(): void
    {
        foreach (self::SUPPORTED_PROVIDERS as $provider) {
            $config = $this->providerConfig($provider);

            config([
                "services.{$provider}.client_id" => $config['client_id'],
                "services.{$provider}.redirect" => $config['redirect'],
            ]);
        }
    }

    private function normalizeProvider(string $provider): string
    {
        $provider = strtolower(trim($provider));

        if (! in_array($provider, self::SUPPORTED_PROVIDERS, true)) {
            throw new InvalidArgumentException('Proveedor social no soportado.');
        }

        return $provider;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function providers(): array
    {
        if ($this->resolvedProviders !== null) {
            return $this->resolvedProviders;
        }

        $this->resolvedProviders = [];

        foreach (self::SUPPORTED_PROVIDERS as $provider) {
            $clientId = trim((string) $this->settings->getString(
                $this->keyFor($provider, 'client_id'),
                (string) config("services.{$provider}.client_id", '')
            ));
            $redirect = trim((string) $this->settings->getString(
                $this->keyFor($provider, 'redirect_uri'),
                (string) config("services.{$provider}.redirect", '')
            ));
            $secret = trim((string) config("services.{$provider}.client_secret", ''));
            $callbackUrl = $this->callbackUrlFor($provider);
            $redirect = $redirect !== '' ? $redirect : $callbackUrl;
            $enabled = $this->toBoolean($this->settings->getString($this->keyFor($provider, 'enabled'), '0'));

            $this->resolvedProviders[$provider] = [
                'key' => $provider,
                'label' => $this->label($provider),
                'enabled' => $enabled,
                'client_id' => $clientId,
                'redirect' => $redirect,
                'callback_url' => $callbackUrl,
                'secret_env' => $provider === self::PROVIDER_GOOGLE ? 'GOOGLE_CLIENT_SECRET' : 'FACEBOOK_APP_SECRET',
                'secret_configured' => $secret !== '',
                'is_ready' => $enabled && $clientId !== '' && $redirect !== '' && $secret !== '',
            ];
        }

        return $this->resolvedProviders;
    }

    private function keyFor(string $provider, string $attribute): string
    {
        return match ($provider) {
            self::PROVIDER_GOOGLE => match ($attribute) {
                'enabled' => self::KEY_GOOGLE_ENABLED,
                'client_id' => self::KEY_GOOGLE_CLIENT_ID,
                'redirect_uri' => self::KEY_GOOGLE_REDIRECT_URI,
            },
            self::PROVIDER_FACEBOOK => match ($attribute) {
                'enabled' => self::KEY_FACEBOOK_ENABLED,
                'client_id' => self::KEY_FACEBOOK_CLIENT_ID,
                'redirect_uri' => self::KEY_FACEBOOK_REDIRECT_URI,
            },
            default => throw new InvalidArgumentException('Proveedor social no soportado.'),
        };
    }

    private function label(string $provider): string
    {
        return match ($provider) {
            self::PROVIDER_GOOGLE => 'Google',
            self::PROVIDER_FACEBOOK => 'Facebook',
            default => ucfirst($provider),
        };
    }

    private function callbackUrlFor(string $provider): string
    {
        try {
            return route('client.social.callback', ['provider' => $provider]);
        } catch (\Throwable) {
            return rtrim((string) config('app.url', ''), '/').'/auth/'.$provider.'/callback';
        }
    }

    private function toBoolean(?string $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'on', 'yes'], true);
    }
}

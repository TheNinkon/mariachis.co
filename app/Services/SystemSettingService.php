<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Crypt;

class SystemSettingService
{
    private const CACHE_PREFIX = 'system_setting:';

    public function __construct(private readonly CacheRepository $cache)
    {
    }

    public function getString(string $key, ?string $default = null): ?string
    {
        $value = $this->cache->rememberForever(self::CACHE_PREFIX.$key, function () use ($key) {
            return SystemSetting::query()
                ->where('key', $key)
                ->first();
        });

        if (! $value instanceof SystemSetting) {
            return $default;
        }

        $raw = (string) ($value->value ?? '');
        if ($raw === '') {
            return $default;
        }

        if (! $value->is_encrypted) {
            return $raw;
        }

        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable) {
            return $default;
        }
    }

    public function putString(string $key, ?string $value, bool $encrypt = false): void
    {
        $normalized = $value === null ? null : trim($value);

        if ($normalized === null || $normalized === '') {
            SystemSetting::query()->where('key', $key)->delete();
            $this->cache->forget(self::CACHE_PREFIX.$key);

            return;
        }

        $storedValue = $encrypt ? Crypt::encryptString($normalized) : $normalized;

        SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'is_encrypted' => $encrypt,
            ]
        );

        $this->cache->forget(self::CACHE_PREFIX.$key);
    }
}

<?php

namespace App\Services;

class MailSettingsService
{
    public const MAILER_SMTP = 'smtp';
    public const MAILER_LOG = 'log';

    public const ENCRYPTION_TLS = 'tls';
    public const ENCRYPTION_SSL = 'ssl';
    public const ENCRYPTION_NONE = 'none';

    public const KEY_MAILER = 'mail_mailer';
    public const KEY_HOST = 'mail_smtp_host';
    public const KEY_PORT = 'mail_smtp_port';
    public const KEY_USERNAME = 'mail_smtp_username';
    public const KEY_PASSWORD = 'mail_smtp_password';
    public const KEY_ENCRYPTION = 'mail_smtp_encryption';
    public const KEY_FROM_ADDRESS = 'mail_from_address';
    public const KEY_FROM_NAME = 'mail_from_name';

    public function __construct(private readonly SystemSettingService $settings)
    {
    }

    /**
     * @return array{
     *   mailer:string,
     *   host:string,
     *   port:int,
     *   username:string,
     *   encryption:string,
     *   from_address:string,
     *   from_name:string,
     *   password_configured:bool
     * }
     */
    public function publicConfig(): array
    {
        return [
            'mailer' => $this->mailer(),
            'host' => $this->host(),
            'port' => $this->port(),
            'username' => $this->username(),
            'encryption' => $this->encryption(),
            'from_address' => $this->fromAddress(),
            'from_name' => $this->fromName(),
            'password_configured' => $this->password() !== '',
        ];
    }

    /**
     * @return array{
     *   default:string,
     *   smtp:array{
     *     transport:string,
     *     host:string,
     *     port:int,
     *     username:?string,
     *     password:?string,
     *     scheme:?string,
     *     encryption:?string,
     *     timeout:mixed,
     *     local_domain:mixed
     *   },
     *   from:array{address:string,name:string}
     * }
     */
    public function runtimeConfig(): array
    {
        $scheme = $this->encryption();
        $resolvedScheme = $scheme === self::ENCRYPTION_NONE ? null : $scheme;
        $username = $this->username();
        $password = $this->password();

        return [
            'default' => $this->mailer(),
            'smtp' => [
                'transport' => 'smtp',
                'host' => $this->host(),
                'port' => $this->port(),
                'username' => $username !== '' ? $username : null,
                'password' => $password !== '' ? $password : null,
                'scheme' => $resolvedScheme,
                'encryption' => $resolvedScheme,
                'timeout' => config('mail.mailers.smtp.timeout'),
                'local_domain' => config('mail.mailers.smtp.local_domain'),
            ],
            'from' => [
                'address' => $this->fromAddress(),
                'name' => $this->fromName(),
            ],
        ];
    }

    public function mailer(): string
    {
        $value = strtolower(trim((string) $this->settings->getString(
            self::KEY_MAILER,
            (string) config('mail.default', self::MAILER_LOG)
        )));

        return in_array($value, [self::MAILER_SMTP, self::MAILER_LOG], true)
            ? $value
            : self::MAILER_LOG;
    }

    public function host(): string
    {
        return $this->normalizedString(
            $this->settings->getString(self::KEY_HOST, (string) config('mail.mailers.smtp.host', '127.0.0.1')),
            '127.0.0.1'
        );
    }

    public function port(): int
    {
        $value = (int) $this->settings->getString(self::KEY_PORT, (string) config('mail.mailers.smtp.port', 587));

        return $value > 0 ? $value : 587;
    }

    public function username(): string
    {
        return trim((string) $this->settings->getString(
            self::KEY_USERNAME,
            (string) config('mail.mailers.smtp.username', '')
        ));
    }

    public function password(): string
    {
        return trim((string) $this->settings->getString(
            self::KEY_PASSWORD,
            (string) config('mail.mailers.smtp.password', '')
        ));
    }

    public function encryption(): string
    {
        $raw = strtolower(trim((string) $this->settings->getString(
            self::KEY_ENCRYPTION,
            (string) (config('mail.mailers.smtp.scheme') ?: self::ENCRYPTION_NONE)
        )));

        return in_array($raw, [self::ENCRYPTION_TLS, self::ENCRYPTION_SSL, self::ENCRYPTION_NONE], true)
            ? $raw
            : self::ENCRYPTION_NONE;
    }

    public function fromAddress(): string
    {
        return $this->normalizedString(
            $this->settings->getString(self::KEY_FROM_ADDRESS, (string) config('mail.from.address', 'hello@example.com')),
            'hello@example.com'
        );
    }

    public function fromName(): string
    {
        return $this->normalizedString(
            $this->settings->getString(self::KEY_FROM_NAME, (string) config('mail.from.name', config('app.name', 'Mariachis.co'))),
            (string) config('app.name', 'Mariachis.co')
        );
    }

    private function normalizedString(?string $value, string $fallback): string
    {
        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : $fallback;
    }
}

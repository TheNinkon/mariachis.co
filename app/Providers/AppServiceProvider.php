<?php

namespace App\Providers;

use App\Services\MailSettingsService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(MailSettingsService $mailSettings): void
    {
        $runtimeConfig = $mailSettings->runtimeConfig();

        config([
            'mail.default' => $runtimeConfig['default'],
            'mail.mailers.smtp.host' => $runtimeConfig['smtp']['host'],
            'mail.mailers.smtp.port' => $runtimeConfig['smtp']['port'],
            'mail.mailers.smtp.username' => $runtimeConfig['smtp']['username'],
            'mail.mailers.smtp.password' => $runtimeConfig['smtp']['password'],
            'mail.mailers.smtp.scheme' => $runtimeConfig['smtp']['scheme'],
            'mail.mailers.smtp.encryption' => $runtimeConfig['smtp']['encryption'],
            'mail.from.address' => $runtimeConfig['from']['address'],
            'mail.from.name' => $runtimeConfig['from']['name'],
        ]);

        if ($this->app->resolved('mail.manager')) {
            $this->app->make('mail.manager')->forgetMailers();
        }

        Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
            if ($src !== null) {
                return [
                    'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src) ? 'template-customizer-core-css' : (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src) ? 'template-customizer-theme-css' : '')
                ];
            }
            return [];
        });
    }
}

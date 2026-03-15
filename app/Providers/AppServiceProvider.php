<?php

namespace App\Providers;

use App\Services\MailSettingsService;
use App\Services\SocialLoginSettingsService;
use App\Support\PortalHosts;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Pagination\Paginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
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
    public function boot(
        MailSettingsService $mailSettings,
        SocialLoginSettingsService $socialLoginSettings
    ): void
    {
        $this->registerRateLimiters();

        Paginator::useBootstrapFive();

        Authenticate::redirectUsing(static function (Request $request): string {
            return route(PortalHosts::loginRouteNameForRequest($request));
        });

        RedirectIfAuthenticated::redirectUsing(static function (Request $request): string {
            return route(PortalHosts::dashboardRouteNameForUser($request->user()));
        });

        if ($this->canResolveMailRuntimeSettings()) {
            $runtimeConfig = $mailSettings->runtimeConfig();

            config([
                'mail.default' => $runtimeConfig['default'],
                'mail.mailers.smtp.host' => $runtimeConfig['smtp']['host'],
                'mail.mailers.smtp.port' => $runtimeConfig['smtp']['port'],
                'mail.mailers.smtp.username' => $runtimeConfig['smtp']['username'],
                'mail.mailers.smtp.password' => $runtimeConfig['smtp']['password'],
                'mail.mailers.smtp.scheme' => $runtimeConfig['smtp']['scheme'],
                'mail.from.address' => $runtimeConfig['from']['address'],
                'mail.from.name' => $runtimeConfig['from']['name'],
            ]);

            if ($this->app->resolved('mail.manager')) {
                $this->app->make('mail.manager')->forgetMailers();
            }

            $socialLoginSettings->applyRuntimeConfig();
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

    private function canResolveMailRuntimeSettings(): bool
    {
        try {
            return Schema::hasTable('system_settings');
        } catch (\Throwable) {
            return false;
        }
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('auth-login', static function (Request $request) {
            $email = mb_strtolower((string) $request->input('email', ''));

            return Limit::perMinute(5)->by(sha1('auth-login|'.$request->ip().'|'.$email));
        });

        RateLimiter::for('password-reset', static function (Request $request) {
            $email = mb_strtolower((string) $request->input('email', ''));

            return Limit::perMinutes(15, 3)->by(sha1('password-reset|'.$request->ip().'|'.$email));
        });

        RateLimiter::for('magic-links', static function (Request $request) {
            $email = mb_strtolower((string) $request->input('email', ''));

            return Limit::perMinutes(10, 5)->by(sha1('magic-link|'.$request->ip().'|'.$email));
        });

        RateLimiter::for('public-interactions', static function (Request $request) {
            $actor = $request->user()?->id ? 'user:'.$request->user()->id : 'ip:'.$request->ip();

            return Limit::perMinute(20)->by(sha1('public-interactions|'.$actor));
        });

        RateLimiter::for('listing-info-requests', static function (Request $request) {
            return Limit::perMinutes(10, 6)->by(
                sha1('listing-info|'.$request->ip().'|'.(string) $request->route('slug'))
            );
        });
    }
}

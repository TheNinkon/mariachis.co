<?php

use App\Http\Controllers\Auth\ClientForgotPasswordController;
use App\Http\Controllers\Auth\ClientLoginController;
use App\Http\Controllers\Auth\ClientRegistrationController;
use App\Http\Controllers\Auth\ClientResetPasswordController;
use App\Http\Controllers\Client\ClientDashboardController;
use App\Http\Controllers\Client\ClientFavoriteController;
use App\Http\Controllers\Client\ClientQuoteConversationController;
use App\Http\Controllers\Client\ClientReviewController;
use App\Http\Controllers\Front\BlogController;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\ListingInfoRequestController;
use App\Http\Controllers\Front\PublicListingCollectionController;
use App\Http\Controllers\Front\PublicMariachiController;
use App\Http\Controllers\Front\PublicProviderController;
use App\Http\Controllers\Front\QuoteRequestController;
use App\Http\Controllers\Front\SeoLandingController;
use App\Http\Controllers\language\LanguageController;
use App\Services\Front\SearchFormData;
use App\Support\PortalHosts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$seoReservedPattern = collect(config('seo.reserved_slugs', []))
    ->map(static fn (string $slug): string => preg_quote($slug, '/'))
    ->implode('|');

$seoLandingSlugPattern = $seoReservedPattern !== ''
    ? '^(?!(?:'.$seoReservedPattern.')$)[a-z0-9-]+$'
    : '^[a-z0-9-]+$';

$legacyAdminRedirect = static function (?string $path = null, Request $request = null) {
    $target = PortalHosts::absoluteUrl(PortalHosts::admin(), $path ?: '/login');

    if ($request?->getQueryString()) {
        $target .= '?'.$request->getQueryString();
    }

    return redirect()->away($target, 302);
};

$legacyPartnerRedirect = static function (?string $path = null, Request $request = null) {
    $target = PortalHosts::absoluteUrl(PortalHosts::partner(), $path ?: '/login');

    if ($request?->getQueryString()) {
        $target .= '?'.$request->getQueryString();
    }

    return redirect()->away($target, 302);
};

collect(config('domains.public_hosts', [config('domains.root')]))
    ->filter()
    ->unique()
    ->each(function (string $publicHost) use (
        $seoLandingSlugPattern,
        $legacyAdminRedirect,
        $legacyPartnerRedirect
    ): void {
Route::domain($publicHost)->group(function () use (
    $seoLandingSlugPattern,
    $legacyAdminRedirect,
    $legacyPartnerRedirect
): void {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
    Route::get('/lista-de-deseos', [PublicListingCollectionController::class, 'wishlist'])->name('public.collections.wishlist');
    Route::get('/vistos-recientemente', [PublicListingCollectionController::class, 'recentlyViewed'])->name('public.collections.recents');
    Route::get('/resolver-anuncios', [PublicListingCollectionController::class, 'resolve'])->name('public.listings.resolve');
    Route::get('/@{handle}', [PublicProviderController::class, 'show'])
        ->where('handle', '[a-z0-9-]+')
        ->name('mariachi.provider.public.show');
    Route::get('/mariachis/{citySlug}/{scopeSlug}', [SeoLandingController::class, 'showCityCategory'])
        ->where(['citySlug' => $seoLandingSlugPattern, 'scopeSlug' => $seoLandingSlugPattern])
        ->name('seo.landing.city-category');
    Route::get('/mariachis/{slug}', [SeoLandingController::class, 'showBySlug'])
        ->where('slug', $seoLandingSlugPattern)
        ->name('seo.landing.slug');

    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [ClientLoginController::class, 'create'])->name('client.login');
        Route::get('/login/email', [ClientLoginController::class, 'showEmailForm'])->name('client.login.email');
        Route::post('/login/email', [ClientLoginController::class, 'captureEmail'])->name('client.login.email.capture');
        Route::get('/login/email/opciones', [ClientLoginController::class, 'showEmailOptions'])->name('client.login.email.options');
        Route::get('/login/email/password', [ClientLoginController::class, 'showPasswordForm'])->name('client.login.password');
        Route::post('/login/email/enlace', [ClientLoginController::class, 'sendMagicLink'])->name('client.login.magic.send');
        Route::get('/login/magic/{token}', [ClientLoginController::class, 'consumeMagicLink'])->name('client.login.magic');
        Route::post('/login', [ClientLoginController::class, 'store'])->name('client.login.attempt');

        Route::get('/registro', [ClientRegistrationController::class, 'create'])->name('client.register');
        Route::post('/registro', [ClientRegistrationController::class, 'store'])->name('client.register.store');

        Route::get('/recuperar-contrasena', [ClientForgotPasswordController::class, 'create'])->name('client.password.request');
        Route::post('/recuperar-contrasena', [ClientForgotPasswordController::class, 'store'])->name('client.password.email');

        Route::get('/restablecer-contrasena/{token}', [ClientResetPasswordController::class, 'create'])->name('client.password.reset');
        Route::post('/restablecer-contrasena', [ClientResetPasswordController::class, 'store'])->name('client.password.update');
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('/auth/logout', [\App\Http\Controllers\Auth\LoginController::class, 'destroy'])->name('client.logout');
        Route::get('/completa-tu-cuenta', [ClientLoginController::class, 'showCompleteAccount'])->name('client.login.complete-account');
        Route::patch('/completa-tu-cuenta', [ClientLoginController::class, 'completeAccount'])->name('client.login.complete-account.update');
    });

    Route::prefix('/mi-cuenta')->middleware('client')->group(function (): void {
        Route::redirect('/', '/mi-cuenta/solicitudes');
        Route::get('/solicitudes', [ClientDashboardController::class, 'show'])->defaults('section', 'solicitudes')->name('client.dashboard');
        Route::get('/favoritos', [ClientDashboardController::class, 'show'])->defaults('section', 'favoritos')->name('client.account.favorites');
        Route::get('/vistos', [ClientDashboardController::class, 'show'])->defaults('section', 'vistos')->name('client.account.recent');
        Route::get('/perfil', [ClientDashboardController::class, 'show'])->defaults('section', 'perfil')->name('client.account.profile');
        Route::get('/seguridad', [ClientDashboardController::class, 'show'])->defaults('section', 'seguridad')->name('client.account.security');
        Route::get('/privacidad', [ClientDashboardController::class, 'show'])->defaults('section', 'privacidad')->name('client.account.privacy');

        Route::patch('/perfil', [ClientDashboardController::class, 'updateProfile'])->name('client.profile.update');
        Route::patch('/seguridad', [ClientDashboardController::class, 'updateSecurity'])->name('client.security.update');
        Route::patch('/privacidad', [ClientDashboardController::class, 'updatePrivacy'])->name('client.privacy.update');
        Route::delete('/desactivar', [ClientDashboardController::class, 'deactivate'])->name('client.deactivate');
        Route::post('/favoritos/{slug}', [ClientFavoriteController::class, 'store'])->name('client.favorites.store');
        Route::delete('/favoritos/{slug}', [ClientFavoriteController::class, 'destroy'])->name('client.favorites.destroy');
        Route::post('/solicitudes/{conversation}/responder', [ClientQuoteConversationController::class, 'reply'])->name('client.quotes.reply');
        Route::post('/solicitudes/{conversation}/opiniones', [ClientReviewController::class, 'store'])->name('client.reviews.store');
    });

    Route::prefix('/cliente')->middleware('client')->group(function (): void {
        Route::redirect('/panel', '/mi-cuenta/solicitudes');
        Route::redirect('/panel/{any}', '/mi-cuenta/solicitudes')->where('any', '.*');
    });

    Route::get('/admin/{path?}', fn (Request $request, ?string $path = null) => $legacyAdminRedirect($path, $request))
        ->where('path', '.*');

    Route::get('/auth/register', fn (Request $request) => $legacyPartnerRedirect('/signup', $request));
    Route::get('/auth/register-basic', fn (Request $request) => $legacyPartnerRedirect('/signup', $request));
    Route::get('/auth/forgot-password-basic', fn (Request $request) => $legacyAdminRedirect('/forgot-password', $request));
    Route::get('/auth/reset-password-basic/{token}', fn (Request $request, string $token) => $legacyAdminRedirect('/reset-password/'.$token, $request));
    Route::get('/mariachi', fn (Request $request) => $legacyPartnerRedirect('/login', $request));
    Route::get('/mariachi/login', fn (Request $request) => $legacyPartnerRedirect('/login', $request));
    Route::get('/mariachi/verificar-correo/{user}/{hash}', fn (Request $request, string $user, string $hash) => $legacyPartnerRedirect('/verificar-correo/'.$user.'/'.$hash, $request));
    Route::get('/mariachi/{portalPath}/{any?}', fn (Request $request, string $portalPath, ?string $any = null) => $legacyPartnerRedirect('/'.$portalPath.'/'.ltrim((string) $any, '/'), $request))
        ->whereIn('portalPath', [
            'metricas',
            'panel',
            'dashboard',
            'solicitudes',
            'opiniones',
            'profile',
            'perfil',
            'perfil-proveedor',
            'verificacion',
            'anuncios',
        ])
        ->where('any', '.*');

    Route::get('/mariachi/{slug}', [PublicMariachiController::class, 'show'])
        ->where('slug', '^(?!login$|panel$|dashboard$|profile$|solicitudes$)[a-z0-9-]+$')
        ->name('mariachi.public.show');
    Route::post('/mariachi/{slug}/solicitar-presupuesto', [QuoteRequestController::class, 'store'])->name('quote.request.store');
    Route::post('/solicitudes-info/{slug}', [ListingInfoRequestController::class, 'store'])->name('listing.info-requests.store');

    Route::get('/lang/{locale}', [LanguageController::class, 'swap']);

    Route::fallback(function (SearchFormData $searchFormData) {
        $payload = $searchFormData->forFallback();

        return response()->view('front.errors.404', $payload, 404);
    });
});
    });

<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CatalogOptionController;
use App\Http\Controllers\Admin\CatalogSuggestionController;
use App\Http\Controllers\Admin\InternalUserController;
use App\Http\Controllers\Admin\AdminListingModerationController;
use App\Http\Controllers\Admin\MariachiController;
use App\Http\Controllers\Admin\MarketplaceCityController;
use App\Http\Controllers\Admin\MarketplaceZoneController;
use App\Http\Controllers\Admin\ProfileVerificationController;
use App\Http\Controllers\Admin\ReviewModerationController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\AdminPlanController;
use App\Http\Controllers\Client\ClientFavoriteController;
use App\Http\Controllers\Client\ClientQuoteConversationController;
use App\Http\Controllers\Client\ClientReviewController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ClientForgotPasswordController;
use App\Http\Controllers\Auth\ClientLoginController;
use App\Http\Controllers\Auth\ClientRegistrationController;
use App\Http\Controllers\Auth\ClientResetPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MariachiRegistrationController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Client\ClientDashboardController;
use App\Http\Controllers\Front\BlogController;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\PublicMariachiController;
use App\Http\Controllers\Front\QuoteRequestController;
use App\Http\Controllers\Front\SeoLandingController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Internal\StaffDashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\language\LanguageController;
use App\Http\Controllers\Mariachi\MariachiDashboardController;
use App\Http\Controllers\Mariachi\MariachiListingController;
use App\Http\Controllers\Mariachi\MariachiProviderProfileController;
use App\Http\Controllers\Mariachi\MariachiQuoteConversationController;
use App\Http\Controllers\Mariachi\MariachiReviewController;
use App\Http\Controllers\Mariachi\MariachiVerificationController;

$seoReservedPattern = collect(config('seo.reserved_slugs', []))
    ->map(static fn (string $slug): string => preg_quote($slug, '/'))
    ->implode('|');

$seoLandingSlugPattern = $seoReservedPattern !== ''
    ? '^(?!(?:'.$seoReservedPattern.')$)[a-z0-9-]+$'
    : '^[a-z0-9-]+$';

Route::get('/', HomeController::class)->name('home');
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/mariachis/{citySlug}/{scopeSlug}', [SeoLandingController::class, 'showCityCategory'])
    ->where(['citySlug' => $seoLandingSlugPattern, 'scopeSlug' => $seoLandingSlugPattern])
    ->name('seo.landing.city-category');
Route::get('/mariachis/{slug}', [SeoLandingController::class, 'showBySlug'])
    ->where('slug', $seoLandingSlugPattern)
    ->name('seo.landing.slug');

Route::redirect('/cliente/login', '/login', 301);
Route::redirect('/cliente/registro', '/registro', 301);
Route::redirect('/cliente/recuperar-contrasena', '/recuperar-contrasena', 301);
Route::redirect('/cliente/restablecer-contrasena/{token}', '/restablecer-contrasena/{token}', 301);

Route::middleware('guest')->group(function (): void {
    Route::get('/admin/login', [LoginController::class, 'create'])->defaults('portal', 'admin')->name('login');
    Route::post('/admin/login', [LoginController::class, 'store'])->defaults('portal', 'admin')->name('login.attempt');

    Route::get('/mariachi/login', [LoginController::class, 'create'])->defaults('portal', 'mariachi')->name('mariachi.login');
    Route::post('/mariachi/login', [LoginController::class, 'store'])->defaults('portal', 'mariachi')->name('mariachi.login.attempt');

    Route::redirect('/auth/login-basic', '/admin/login', 301);
    Route::post('/auth/login-basic', [LoginController::class, 'store'])->defaults('portal', 'admin');

    Route::get('/auth/register-basic', [MariachiRegistrationController::class, 'create'])->name('register');
    Route::post('/auth/register-basic', [MariachiRegistrationController::class, 'store'])->name('register.mariachi');

    Route::get('/auth/forgot-password-basic', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/auth/forgot-password-basic', [ForgotPasswordController::class, 'store'])->name('password.email');

    Route::get('/auth/reset-password-basic/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/auth/reset-password-basic', [ResetPasswordController::class, 'store'])->name('password.update');

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
    Route::post('/auth/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::prefix('/admin')->middleware('role:admin')->group(function (): void {
        Route::get('/', fn () => redirect()->route('admin.dashboard'))->name('admin.panel');
        Route::get('/dashboard', AdminDashboardController::class)->name('admin.dashboard');
        Route::get('/mariachis', [MariachiController::class, 'index'])->name('admin.mariachis.index');
        Route::patch('/mariachis/{user}/toggle-status', [MariachiController::class, 'toggleStatus'])->name('admin.mariachis.toggle-status');
        Route::get('/mariachis/{user}', [MariachiController::class, 'show'])->name('admin.mariachis.show');
        Route::get('/mariachis/{user}/editar', [MariachiController::class, 'edit'])->name('admin.mariachis.edit');
        Route::patch('/mariachis/{user}', [MariachiController::class, 'update'])->name('admin.mariachis.update');
        Route::patch('/mariachis/{user}/plan', [MariachiController::class, 'assignPlan'])->name('admin.mariachis.assign-plan');
        Route::get('/anuncios', [AdminListingModerationController::class, 'index'])->name('admin.listings.index');
        Route::get('/anuncios/{listing}', [AdminListingModerationController::class, 'show'])->name('admin.listings.show');
        Route::patch('/anuncios/{listing}/moderar', [AdminListingModerationController::class, 'moderate'])->name('admin.listings.moderate');
        Route::get('/paquetes', [AdminPlanController::class, 'index'])->name('admin.plans.index');
        Route::get('/paquetes/crear', [AdminPlanController::class, 'create'])->name('admin.plans.create');
        Route::post('/paquetes', [AdminPlanController::class, 'store'])->name('admin.plans.store');
        Route::get('/paquetes/{plan}/editar', [AdminPlanController::class, 'edit'])->name('admin.plans.edit');
        Route::put('/paquetes/{plan}', [AdminPlanController::class, 'update'])->name('admin.plans.update');
        Route::patch('/paquetes/{plan}/toggle-status', [AdminPlanController::class, 'toggleStatus'])->name('admin.plans.toggle-status');
        Route::get('/resenas', [ReviewModerationController::class, 'index'])->name('admin.reviews.index');
        Route::patch('/resenas/{review}/moderar', [ReviewModerationController::class, 'moderate'])->name('admin.reviews.moderate');
        Route::patch('/resenas/{review}/verificacion', [ReviewModerationController::class, 'updateVerification'])->name('admin.reviews.verification');
        Route::patch('/resenas/{review}/respuesta', [ReviewModerationController::class, 'moderateReply'])->name('admin.reviews.reply');
        Route::get('/verificaciones-perfil', [ProfileVerificationController::class, 'index'])->name('admin.profile-verifications.index');
        Route::patch('/verificaciones-perfil/{verificationRequest}', [ProfileVerificationController::class, 'update'])->name('admin.profile-verifications.update');
        Route::get('/internal-users', [InternalUserController::class, 'index'])->name('admin.internal-users.index');
        Route::post('/internal-users', [InternalUserController::class, 'store'])->name('admin.internal-users.store');
        Route::patch('/internal-users/{user}/toggle-status', [InternalUserController::class, 'toggleStatus'])->name('admin.internal-users.toggle-status');
        Route::get('/configuracion-sistema', [SystemSettingController::class, 'edit'])->name('admin.system-settings.edit');
        Route::patch('/configuracion-sistema', [SystemSettingController::class, 'update'])->name('admin.system-settings.update');
        Route::resource('/blog-posts', BlogPostController::class)
            ->except(['show'])
            ->names('admin.blog-posts');

        Route::prefix('/catalogos')->group(function (): void {
            Route::get('/{catalog}', [CatalogOptionController::class, 'index'])
                ->whereIn('catalog', ['event-types', 'service-types', 'group-sizes', 'budget-ranges'])
                ->name('admin.catalog-options.index');
            Route::get('/{catalog}/crear', [CatalogOptionController::class, 'create'])
                ->whereIn('catalog', ['event-types', 'service-types', 'group-sizes', 'budget-ranges'])
                ->name('admin.catalog-options.create');
            Route::post('/{catalog}', [CatalogOptionController::class, 'store'])
                ->whereIn('catalog', ['event-types', 'service-types', 'group-sizes', 'budget-ranges'])
                ->name('admin.catalog-options.store');
            Route::get('/{catalog}/{id}/editar', [CatalogOptionController::class, 'edit'])
                ->whereIn('catalog', ['event-types', 'service-types', 'group-sizes', 'budget-ranges'])
                ->whereNumber('id')
                ->name('admin.catalog-options.edit');
            Route::put('/{catalog}/{id}', [CatalogOptionController::class, 'update'])
                ->whereIn('catalog', ['event-types', 'service-types', 'group-sizes', 'budget-ranges'])
                ->whereNumber('id')
                ->name('admin.catalog-options.update');
            Route::patch('/{catalog}/{id}/toggle-status', [CatalogOptionController::class, 'toggleStatus'])
                ->whereIn('catalog', ['event-types', 'service-types', 'group-sizes', 'budget-ranges'])
                ->whereNumber('id')
                ->name('admin.catalog-options.toggle-status');
        });

        Route::get('/catalogos-ciudades', [MarketplaceCityController::class, 'index'])->name('admin.marketplace-cities.index');
        Route::get('/catalogos-ciudades/crear', [MarketplaceCityController::class, 'create'])->name('admin.marketplace-cities.create');
        Route::post('/catalogos-ciudades', [MarketplaceCityController::class, 'store'])->name('admin.marketplace-cities.store');
        Route::get('/catalogos-ciudades/{marketplaceCity}/editar', [MarketplaceCityController::class, 'edit'])->name('admin.marketplace-cities.edit');
        Route::put('/catalogos-ciudades/{marketplaceCity}', [MarketplaceCityController::class, 'update'])->name('admin.marketplace-cities.update');
        Route::patch('/catalogos-ciudades/{marketplaceCity}/toggle-status', [MarketplaceCityController::class, 'toggleStatus'])->name('admin.marketplace-cities.toggle-status');

        Route::get('/catalogos-zonas', [MarketplaceZoneController::class, 'index'])->name('admin.marketplace-zones.index');
        Route::get('/catalogos-zonas/crear', [MarketplaceZoneController::class, 'create'])->name('admin.marketplace-zones.create');
        Route::post('/catalogos-zonas', [MarketplaceZoneController::class, 'store'])->name('admin.marketplace-zones.store');
        Route::get('/catalogos-zonas/{marketplaceZone}/editar', [MarketplaceZoneController::class, 'edit'])->name('admin.marketplace-zones.edit');
        Route::put('/catalogos-zonas/{marketplaceZone}', [MarketplaceZoneController::class, 'update'])->name('admin.marketplace-zones.update');
        Route::patch('/catalogos-zonas/{marketplaceZone}/toggle-status', [MarketplaceZoneController::class, 'toggleStatus'])->name('admin.marketplace-zones.toggle-status');

        Route::get('/catalogos-sugerencias', [CatalogSuggestionController::class, 'index'])->name('admin.catalog-suggestions.index');
        Route::patch('/catalogos-sugerencias/{catalogSuggestion}/aprobar', [CatalogSuggestionController::class, 'approve'])->name('admin.catalog-suggestions.approve');
        Route::patch('/catalogos-sugerencias/{catalogSuggestion}/rechazar', [CatalogSuggestionController::class, 'reject'])->name('admin.catalog-suggestions.reject');
    });

    Route::prefix('/staff')->middleware('role:staff')->group(function (): void {
        Route::get('/dashboard', StaffDashboardController::class)->name('staff.dashboard');
    });

    Route::prefix('/mariachi')->middleware('role:mariachi')->group(function (): void {
        Route::get('/', fn () => redirect()->route('mariachi.metrics'))->name('mariachi.root');
        Route::get('/metricas', MariachiDashboardController::class)->name('mariachi.metrics');
        Route::get('/panel', fn () => redirect()->route('mariachi.metrics'))->name('mariachi.panel');
        Route::get('/dashboard', fn () => redirect()->route('mariachi.metrics'))->name('mariachi.dashboard');
        Route::get('/solicitudes', [MariachiQuoteConversationController::class, 'index'])->name('mariachi.quotes.index');
        Route::get('/opiniones', [MariachiReviewController::class, 'index'])->name('mariachi.reviews.index');
        Route::post('/opiniones/{review}/responder', [MariachiReviewController::class, 'reply'])->name('mariachi.reviews.reply');
        Route::post('/opiniones/{review}/reportar', [MariachiReviewController::class, 'report'])->name('mariachi.reviews.report');
        Route::post('/solicitudes/{conversation}/responder', [MariachiQuoteConversationController::class, 'reply'])->name('mariachi.quotes.reply');
        Route::patch('/solicitudes/{conversation}/estado', [MariachiQuoteConversationController::class, 'updateStatus'])->name('mariachi.quotes.status');
        Route::get('/profile', fn () => redirect()->route('mariachi.provider-profile.edit'))->name('mariachi.profile.index');
        Route::get('/perfil-proveedor', [MariachiProviderProfileController::class, 'edit'])->name('mariachi.provider-profile.edit');
        Route::patch('/perfil-proveedor', [MariachiProviderProfileController::class, 'update'])->name('mariachi.provider-profile.update');
        Route::get('/verificacion', [MariachiVerificationController::class, 'edit'])->name('mariachi.verification.edit');
        Route::post('/verificacion', [MariachiVerificationController::class, 'store'])->name('mariachi.verification.store');

        Route::get('/anuncios', [MariachiListingController::class, 'index'])->name('mariachi.listings.index');
        Route::get('/anuncios/crear', [MariachiListingController::class, 'create'])->name('mariachi.listings.create');
        Route::post('/anuncios', [MariachiListingController::class, 'store'])->name('mariachi.listings.store');
        Route::get('/anuncios/{listing}/planes', [MariachiListingController::class, 'plans'])->name('mariachi.listings.plans');
        Route::post('/anuncios/{listing}/planes', [MariachiListingController::class, 'selectPlan'])->name('mariachi.listings.plans.select');
        Route::get('/anuncios/{listing}/editar', [MariachiListingController::class, 'edit'])->name('mariachi.listings.edit');
        Route::patch('/anuncios/{listing}/autosave', [MariachiListingController::class, 'autosave'])->name('mariachi.listings.autosave');
        Route::patch('/anuncios/{listing}', [MariachiListingController::class, 'update'])->name('mariachi.listings.update');
        Route::post('/anuncios/{listing}/enviar-revision', [MariachiListingController::class, 'submitForReview'])->name('mariachi.listings.submit-review');
        Route::post('/anuncios/{listing}/fotos', [MariachiListingController::class, 'uploadPhoto'])->name('mariachi.listings.photos.store');
        Route::delete('/anuncios/{listing}/fotos/{photo}', [MariachiListingController::class, 'deletePhoto'])->name('mariachi.listings.photos.delete');
        Route::patch('/anuncios/{listing}/fotos/{photo}/destacar', [MariachiListingController::class, 'setFeaturedPhoto'])->name('mariachi.listings.photos.featured');
        Route::patch('/anuncios/{listing}/fotos/{photo}/move/{direction}', [MariachiListingController::class, 'movePhoto'])->name('mariachi.listings.photos.move');
        Route::post('/anuncios/{listing}/videos', [MariachiListingController::class, 'storeVideo'])->name('mariachi.listings.videos.store');
        Route::delete('/anuncios/{listing}/videos/{video}', [MariachiListingController::class, 'deleteVideo'])->name('mariachi.listings.videos.delete');
    });

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

Route::get('/mariachi/{slug}', [PublicMariachiController::class, 'show'])
    ->where('slug', '^(?!login$|panel$|dashboard$|profile$|solicitudes$)[a-z0-9-]+$')
    ->name('mariachi.public.show');
Route::post('/mariachi/{slug}/solicitar-presupuesto', [QuoteRequestController::class, 'store'])->name('quote.request.store');

Route::get('/lang/{locale}', [LanguageController::class, 'swap']);

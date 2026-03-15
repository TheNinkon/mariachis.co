<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminEmailTemplateController;
use App\Http\Controllers\Admin\AdminListingModerationController;
use App\Http\Controllers\Admin\AdminListingPaymentController;
use App\Http\Controllers\Admin\AdminPlanController;
use App\Http\Controllers\Admin\AccountActivationPaymentController;
use App\Http\Controllers\Admin\AccountActivationPlanController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\CatalogOptionController;
use App\Http\Controllers\Admin\CatalogSuggestionController;
use App\Http\Controllers\Admin\InternalUserController;
use App\Http\Controllers\Admin\MariachiController;
use App\Http\Controllers\Admin\MarketplaceCityController;
use App\Http\Controllers\Admin\MarketplaceZoneController;
use App\Http\Controllers\Admin\ProfileVerificationPlanController;
use App\Http\Controllers\Admin\ProfileVerificationController;
use App\Http\Controllers\Admin\ReviewModerationController;
use App\Http\Controllers\Admin\SeoAiController;
use App\Http\Controllers\Admin\SeoPageController;
use App\Http\Controllers\Admin\SeoSettingsController;
use App\Http\Controllers\Admin\SeoToolsController;
use App\Http\Controllers\Admin\SocialLoginSettingsController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Internal\StaffDashboardController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::domain(config('domains.admin'))->group(function (): void {
    Route::get('/', function () {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        return $user->isStaff()
            ? redirect()->route('staff.dashboard')
            : redirect()->route('admin.dashboard');
    });

    Route::redirect('/auth/login-basic', '/login', 301);
    Route::redirect('/auth/forgot-password-basic', '/forgot-password', 301);
    Route::redirect('/auth/reset-password-basic/{token}', '/reset-password/{token}', 301);

    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [LoginController::class, 'create'])->defaults('portal', 'admin')->name('login');
        Route::post('/login', [LoginController::class, 'store'])
            ->middleware('throttle:auth-login')
            ->defaults('portal', 'admin')
            ->name('login.attempt');

        Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->defaults('portal', 'admin')->name('password.request');
        Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
            ->middleware('throttle:password-reset')
            ->defaults('portal', 'admin')
            ->name('password.email');

        Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->defaults('portal', 'admin')->name('password.reset');
        Route::post('/reset-password', [ResetPasswordController::class, 'store'])
            ->middleware('throttle:password-reset')
            ->defaults('portal', 'admin')
            ->name('password.update');
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('/logout', [LoginController::class, 'destroy'])->name('admin.logout');

        Route::middleware('role:admin')->group(function (): void {
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
            Route::get('/pagos', [AdminListingPaymentController::class, 'index'])->name('admin.payments.index');
            Route::patch('/pagos/{listingPayment}', [AdminListingPaymentController::class, 'update'])->name('admin.payments.update');
            Route::get('/paquetes', [AdminPlanController::class, 'index'])->name('admin.plans.index');
            Route::get('/paquetes/crear', [AdminPlanController::class, 'create'])->name('admin.plans.create');
            Route::post('/paquetes', [AdminPlanController::class, 'store'])->name('admin.plans.store');
            Route::get('/paquetes/activacion', [AccountActivationPlanController::class, 'index'])->name('admin.account-activation-plans.index');
            Route::get('/paquetes/activacion/crear', [AccountActivationPlanController::class, 'create'])->name('admin.account-activation-plans.create');
            Route::post('/paquetes/activacion', [AccountActivationPlanController::class, 'store'])->name('admin.account-activation-plans.store');
            Route::get('/paquetes/activacion/{accountActivationPlan}/editar', [AccountActivationPlanController::class, 'edit'])->name('admin.account-activation-plans.edit');
            Route::put('/paquetes/activacion/{accountActivationPlan}', [AccountActivationPlanController::class, 'update'])->name('admin.account-activation-plans.update');
            Route::patch('/paquetes/activacion/{accountActivationPlan}/toggle-status', [AccountActivationPlanController::class, 'toggleStatus'])->name('admin.account-activation-plans.toggle-status');
            Route::get('/paquetes/verificacion', [ProfileVerificationPlanController::class, 'index'])->name('admin.profile-verification-plans.index');
            Route::get('/paquetes/verificacion/crear', [ProfileVerificationPlanController::class, 'create'])->name('admin.profile-verification-plans.create');
            Route::post('/paquetes/verificacion', [ProfileVerificationPlanController::class, 'store'])->name('admin.profile-verification-plans.store');
            Route::get('/paquetes/verificacion/{profileVerificationPlan}/editar', [ProfileVerificationPlanController::class, 'edit'])->name('admin.profile-verification-plans.edit');
            Route::put('/paquetes/verificacion/{profileVerificationPlan}', [ProfileVerificationPlanController::class, 'update'])->name('admin.profile-verification-plans.update');
            Route::patch('/paquetes/verificacion/{profileVerificationPlan}/toggle-status', [ProfileVerificationPlanController::class, 'toggleStatus'])->name('admin.profile-verification-plans.toggle-status');
            Route::get('/pagos-activacion', [AccountActivationPaymentController::class, 'index'])->name('admin.account-activation-payments.index');
            Route::patch('/pagos-activacion/{accountActivationPayment}', [AccountActivationPaymentController::class, 'update'])->name('admin.account-activation-payments.update');
            Route::get('/paquetes/{plan}/editar', [AdminPlanController::class, 'edit'])->name('admin.plans.edit');
            Route::put('/paquetes/{plan}', [AdminPlanController::class, 'update'])->name('admin.plans.update');
            Route::patch('/paquetes/{plan}/toggle-status', [AdminPlanController::class, 'toggleStatus'])->name('admin.plans.toggle-status');
            Route::get('/plantillas-correo', [AdminEmailTemplateController::class, 'index'])->name('admin.email-templates.index');
            Route::get('/plantillas-correo/{key}', [AdminEmailTemplateController::class, 'edit'])->name('admin.email-templates.edit');
            Route::post('/plantillas-correo/{key}/preview', [AdminEmailTemplateController::class, 'preview'])->name('admin.email-templates.preview');
            Route::patch('/plantillas-correo/{key}', [AdminEmailTemplateController::class, 'update'])->name('admin.email-templates.update');
            Route::post('/plantillas-correo/{key}/test', [AdminEmailTemplateController::class, 'sendTest'])->name('admin.email-templates.test');
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
            Route::post('/configuracion-sistema/smtp/test', [SystemSettingController::class, 'sendMailTest'])->name('admin.system-settings.smtp.test');
            Route::get('/configuracion-social-login', [SocialLoginSettingsController::class, 'edit'])->name('admin.social-login-settings.edit');
            Route::patch('/configuracion-social-login', [SocialLoginSettingsController::class, 'update'])->name('admin.social-login-settings.update');
            Route::get('/seo/configuracion', [SeoSettingsController::class, 'edit'])->name('admin.seo-settings.edit');
            Route::patch('/seo/configuracion', [SeoSettingsController::class, 'update'])->name('admin.seo-settings.update');
            Route::get('/seo/ia', [SeoAiController::class, 'edit'])->name('admin.seo-ai.edit');
            Route::patch('/seo/ia', [SeoAiController::class, 'update'])->name('admin.seo-ai.update');
            Route::post('/seo/ia/probar-conexion', [SeoAiController::class, 'testConnection'])->name('admin.seo-ai.test');
            Route::post('/seo/generate', [SeoAiController::class, 'generate'])->name('admin.seo-ai.generate');
            Route::post('/seo/suggest-canonical', [SeoToolsController::class, 'suggestCanonical'])->name('admin.seo-tools.canonical');
            Route::post('/seo/generate-jsonld', [SeoToolsController::class, 'generateJsonLd'])->name('admin.seo-tools.jsonld');
            Route::get('/seo/paginas', [SeoPageController::class, 'index'])->name('admin.seo-pages.index');
            Route::get('/seo/paginas/{seoPage}/editar', [SeoPageController::class, 'edit'])->name('admin.seo-pages.edit');
            Route::put('/seo/paginas/{seoPage}', [SeoPageController::class, 'update'])->name('admin.seo-pages.update');
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

        Route::middleware('role:staff')->group(function (): void {
            Route::get('/staff/dashboard', StaffDashboardController::class)->name('staff.dashboard');
        });
    });
});

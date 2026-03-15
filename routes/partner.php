<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MariachiRegistrationController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Mariachi\MariachiAccountSettingsController;
use App\Http\Controllers\Mariachi\MariachiDashboardController;
use App\Http\Controllers\Mariachi\MariachiListingController;
use App\Http\Controllers\Mariachi\MariachiProviderProfileController;
use App\Http\Controllers\Mariachi\MariachiQuoteConversationController;
use App\Http\Controllers\Mariachi\MariachiReviewController;
use App\Http\Controllers\Mariachi\MariachiVerificationController;
use App\Http\Controllers\Mariachi\WompiPaymentController;
use Illuminate\Support\Facades\Route;

Route::domain(config('domains.partner'))->group(function (): void {
    Route::get('/', function () {
        return auth()->check()
            ? redirect()->route('mariachi.metrics')
            : redirect()->route('mariachi.login');
    });

    Route::redirect('/auth/register-basic', '/signup', 301);
    Route::redirect('/auth/register', '/signup', 301);
    Route::redirect('/register', '/signup', 301);
    Route::redirect('/auth/forgot-password-basic', '/forgot-password', 301);
    Route::redirect('/auth/reset-password-basic/{token}', '/reset-password/{token}', 301);

    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [LoginController::class, 'create'])->defaults('portal', 'mariachi')->name('mariachi.login');
        Route::post('/login', [LoginController::class, 'store'])
            ->middleware('throttle:auth-login')
            ->defaults('portal', 'mariachi')
            ->name('mariachi.login.attempt');

        Route::get('/signup', [MariachiRegistrationController::class, 'create'])->name('mariachi.register');
        Route::post('/signup', [MariachiRegistrationController::class, 'store'])->name('mariachi.register.store');
        Route::get('/signup/activar/{user}/{token}', [MariachiRegistrationController::class, 'activation'])->name('mariachi.activation.show');
        Route::post('/signup/activar/{user}/{token}/wompi', [MariachiRegistrationController::class, 'startActivationCheckout'])->name('mariachi.activation.payments.wompi.checkout');
        Route::get('/pagos/wompi/{type}/{reference}', [WompiPaymentController::class, 'redirect'])
            ->whereIn('type', ['activation', 'listing', 'verification'])
            ->name('mariachi.wompi.redirect');
        Route::post('/pagos/wompi/webhook', [WompiPaymentController::class, 'webhook'])->name('mariachi.wompi.webhook');

        Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->defaults('portal', 'mariachi')->name('mariachi.password.request');
        Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
            ->middleware('throttle:password-reset')
            ->defaults('portal', 'mariachi')
            ->name('mariachi.password.email');

        Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->defaults('portal', 'mariachi')->name('mariachi.password.reset');
        Route::post('/reset-password', [ResetPasswordController::class, 'store'])
            ->middleware('throttle:password-reset')
            ->defaults('portal', 'mariachi')
            ->name('mariachi.password.update');

        Route::get('/verificar-correo/{user}/{hash}', [MariachiRegistrationController::class, 'verifyEmail'])
            ->middleware('signed')
            ->name('mariachi.register.verify');
    });

    Route::middleware(['auth', 'role:mariachi'])->group(function (): void {
        Route::post('/logout', [LoginController::class, 'destroy'])->name('partner.logout');

        Route::get('/metricas', MariachiDashboardController::class)->name('mariachi.metrics');
        Route::get('/panel', fn () => redirect()->route('mariachi.metrics'))->name('mariachi.panel');
        Route::get('/dashboard', fn () => redirect()->route('mariachi.metrics'))->name('mariachi.dashboard');
        Route::get('/solicitudes', [MariachiQuoteConversationController::class, 'index'])->name('mariachi.quotes.index');
        Route::get('/opiniones', [MariachiReviewController::class, 'index'])->name('mariachi.reviews.index');
        Route::post('/opiniones/{review}/responder', [MariachiReviewController::class, 'reply'])->name('mariachi.reviews.reply');
        Route::post('/opiniones/{review}/reportar', [MariachiReviewController::class, 'report'])->name('mariachi.reviews.report');
        Route::post('/solicitudes/{conversation}/responder', [MariachiQuoteConversationController::class, 'reply'])->name('mariachi.quotes.reply');
        Route::patch('/solicitudes/{conversation}/estado', [MariachiQuoteConversationController::class, 'updateStatus'])->name('mariachi.quotes.status');
        Route::redirect('/profile', '/perfil', 301)->name('mariachi.profile.index');
        Route::redirect('/perfil-proveedor', '/perfil', 301);
        Route::get('/perfil', [MariachiProviderProfileController::class, 'edit'])->name('mariachi.provider-profile.edit');
        Route::patch('/perfil', [MariachiProviderProfileController::class, 'update'])->name('mariachi.provider-profile.update');
        Route::get('/cuenta/seguridad', [MariachiAccountSettingsController::class, 'security'])->name('mariachi.account.security.edit');
        Route::patch('/cuenta/seguridad', [MariachiAccountSettingsController::class, 'updateSecurity'])->name('mariachi.account.security.update');
        Route::get('/cuenta/notificaciones', [MariachiAccountSettingsController::class, 'notifications'])->name('mariachi.account.notifications.edit');
        Route::patch('/cuenta/notificaciones', [MariachiAccountSettingsController::class, 'updateNotifications'])->name('mariachi.account.notifications.update');
        Route::get('/cuenta/facturacion', [MariachiAccountSettingsController::class, 'billing'])->name('mariachi.account.billing.edit');
        Route::get('/verificacion', [MariachiVerificationController::class, 'edit'])->name('mariachi.verification.edit');
        Route::post('/verificacion', [MariachiVerificationController::class, 'store'])->name('mariachi.verification.store');
        Route::patch('/verificacion/handle', [MariachiVerificationController::class, 'updateHandle'])->name('mariachi.verification.handle.update');

        Route::get('/anuncios', [MariachiListingController::class, 'index'])->name('mariachi.listings.index');
        Route::get('/anuncios/crear', [MariachiListingController::class, 'create'])->name('mariachi.listings.create');
        Route::post('/anuncios', [MariachiListingController::class, 'store'])->name('mariachi.listings.store');
        Route::get('/anuncios/{listing}/planes', [MariachiListingController::class, 'plans'])->name('mariachi.listings.plans');
        Route::post('/anuncios/{listing}/planes', [MariachiListingController::class, 'selectPlan'])->name('mariachi.listings.plans.select');
        Route::post('/anuncios/{listing}/pagos/wompi', [MariachiListingController::class, 'startWompiCheckout'])->name('mariachi.listings.payments.wompi.checkout');
        Route::get('/anuncios/{listing}/editar', [MariachiListingController::class, 'edit'])->name('mariachi.listings.edit');
        Route::patch('/anuncios/{listing}/autosave', [MariachiListingController::class, 'autosave'])->name('mariachi.listings.autosave');
        Route::patch('/anuncios/{listing}', [MariachiListingController::class, 'update'])->name('mariachi.listings.update');
        Route::post('/anuncios/{listing}/pause', [MariachiListingController::class, 'pause'])->name('mariachi.listings.pause');
        Route::post('/anuncios/{listing}/resume', [MariachiListingController::class, 'resume'])->name('mariachi.listings.resume');
        Route::post('/anuncios/{listing}/enviar-revision', [MariachiListingController::class, 'submitForReview'])->name('mariachi.listings.submit-review');
        Route::post('/anuncios/{listing}/fotos', [MariachiListingController::class, 'uploadPhoto'])->name('mariachi.listings.photos.store');
        Route::delete('/anuncios/{listing}/fotos/{photo}', [MariachiListingController::class, 'deletePhoto'])->name('mariachi.listings.photos.delete');
        Route::patch('/anuncios/{listing}/fotos/{photo}/destacar', [MariachiListingController::class, 'setFeaturedPhoto'])->name('mariachi.listings.photos.featured');
        Route::patch('/anuncios/{listing}/fotos/{photo}/move/{direction}', [MariachiListingController::class, 'movePhoto'])->name('mariachi.listings.photos.move');
        Route::post('/anuncios/{listing}/videos', [MariachiListingController::class, 'storeVideo'])->name('mariachi.listings.videos.store');
        Route::delete('/anuncios/{listing}/videos/{video}', [MariachiListingController::class, 'deleteVideo'])->name('mariachi.listings.videos.delete');
    });
});

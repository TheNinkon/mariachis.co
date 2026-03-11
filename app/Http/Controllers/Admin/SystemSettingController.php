<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SystemSmtpTestMail;
use App\Services\GoogleMapsSettingsService;
use App\Services\MailSettingsService;
use App\Services\SystemSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class SystemSettingController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $settings,
        private readonly GoogleMapsSettingsService $googleMapsSettings,
        private readonly MailSettingsService $mailSettings
    ) {
    }

    public function edit(): View
    {
        return view('content.admin.system-settings', [
            'googleMaps' => $this->googleMapsSettings->publicConfig(),
            'smtp' => $this->mailSettings->publicConfig(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'google_maps_api_key' => ['nullable', 'string', 'max:2048'],
            'clear_google_maps_api_key' => ['nullable', 'boolean'],
            'google_places_country_restriction' => ['required', 'string', 'size:2'],
            'marketplace_default_country_name' => ['required', 'string', 'max:120'],
            'marketplace_default_country_code' => ['required', 'string', 'size:2'],
            'mail_mailer' => ['required', 'string', 'in:smtp,log'],
            'mail_smtp_host' => ['nullable', 'string', 'max:255'],
            'mail_smtp_port' => ['nullable', 'integer', 'between:1,65535'],
            'mail_smtp_username' => ['nullable', 'string', 'max:255'],
            'mail_smtp_password' => ['nullable', 'string', 'max:255'],
            'clear_mail_smtp_password' => ['nullable', 'boolean'],
            'mail_smtp_encryption' => ['required', 'string', 'in:tls,ssl,none'],
            'mail_from_address' => ['required', 'email:rfc', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:255'],
        ]);

        if ($validated['mail_mailer'] === MailSettingsService::MAILER_SMTP) {
            $request->validate([
                'mail_smtp_host' => ['required', 'string', 'max:255'],
                'mail_smtp_port' => ['required', 'integer', 'between:1,65535'],
            ]);
        }

        if ($request->boolean('clear_google_maps_api_key')) {
            $this->settings->putString(GoogleMapsSettingsService::KEY_BROWSER_API, null, true);
        } elseif (filled($validated['google_maps_api_key'] ?? null)) {
            $this->settings->putString(
                GoogleMapsSettingsService::KEY_BROWSER_API,
                $validated['google_maps_api_key'],
                true
            );
        }

        $this->settings->putString(
            GoogleMapsSettingsService::KEY_COUNTRY_RESTRICTION,
            strtolower($validated['google_places_country_restriction'])
        );
        $this->settings->putString(
            GoogleMapsSettingsService::KEY_DEFAULT_COUNTRY_NAME,
            $validated['marketplace_default_country_name']
        );
        $this->settings->putString(
            GoogleMapsSettingsService::KEY_DEFAULT_COUNTRY_CODE,
            strtoupper($validated['marketplace_default_country_code'])
        );

        $this->settings->putString(
            MailSettingsService::KEY_MAILER,
            $validated['mail_mailer']
        );
        $this->settings->putString(
            MailSettingsService::KEY_HOST,
            $validated['mail_smtp_host'] ?? null
        );
        $this->settings->putString(
            MailSettingsService::KEY_PORT,
            isset($validated['mail_smtp_port']) ? (string) $validated['mail_smtp_port'] : null
        );
        $this->settings->putString(
            MailSettingsService::KEY_USERNAME,
            $validated['mail_smtp_username'] ?? null
        );

        if ($request->boolean('clear_mail_smtp_password')) {
            $this->settings->putString(MailSettingsService::KEY_PASSWORD, null, true);
        } elseif (filled($validated['mail_smtp_password'] ?? null)) {
            $this->settings->putString(
                MailSettingsService::KEY_PASSWORD,
                $validated['mail_smtp_password'],
                true
            );
        }

        $this->settings->putString(
            MailSettingsService::KEY_ENCRYPTION,
            $validated['mail_smtp_encryption']
        );
        $this->settings->putString(
            MailSettingsService::KEY_FROM_ADDRESS,
            $validated['mail_from_address']
        );
        $this->settings->putString(
            MailSettingsService::KEY_FROM_NAME,
            $validated['mail_from_name']
        );

        return back()->with('status', 'Configuración del sistema actualizada.');
    }

    public function sendMailTest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mail_test_recipient' => ['required', 'email:rfc', 'max:255'],
        ]);

        $smtp = $this->mailSettings->publicConfig();

        try {
            Mail::to($validated['mail_test_recipient'])->send(
                new SystemSmtpTestMail(
                    $validated['mail_test_recipient'],
                    $smtp['mailer'],
                    $smtp['from_address'],
                    $smtp['from_name']
                )
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'mail_test_recipient' => 'No pudimos enviar el correo de prueba con la configuración actual.',
            ])->withInput();
        }

        $message = $smtp['mailer'] === MailSettingsService::MAILER_LOG
            ? 'La prueba se procesó con el mailer "log". Revisa el log del sistema si quieres ver el contenido.'
            : 'Correo de prueba enviado correctamente.';

        return back()->with('status', $message);
    }
}

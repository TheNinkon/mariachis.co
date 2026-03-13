<?php

namespace App\Services;

class NequiPaymentSettingsService
{
    public const KEY_PHONE = 'nequi_phone';
    public const KEY_BENEFICIARY_NAME = 'nequi_beneficiary_name';
    public const KEY_QR_IMAGE_PATH = 'nequi_qr_image_path';

    public function __construct(private readonly SystemSettingService $settings)
    {
    }

    /**
     * @return array{phone:string,beneficiary_name:string,qr_image_path:?string,qr_image_url:?string,is_configured:bool}
     */
    public function publicConfig(): array
    {
        $qrPath = $this->settings->getString(self::KEY_QR_IMAGE_PATH, config('payments.nequi.qr_image_path'));

        return [
            'phone' => $this->settings->getString(self::KEY_PHONE, config('payments.nequi.phone', '')) ?? '',
            'beneficiary_name' => $this->settings->getString(self::KEY_BENEFICIARY_NAME, config('payments.nequi.beneficiary_name', '')) ?? '',
            'qr_image_path' => $qrPath,
            // Use the current request host instead of APP_URL so the QR works on admin.localhost and partner.localhost.
            'qr_image_url' => $qrPath ? asset('storage/'.$qrPath) : null,
            'is_configured' => filled($this->settings->getString(self::KEY_PHONE, config('payments.nequi.phone', ''))),
        ];
    }
}

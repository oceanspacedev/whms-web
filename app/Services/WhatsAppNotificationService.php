<?php

namespace App\Services;

use RuntimeException;

class WhatsAppNotificationService
{
    public function __construct(
        private WhatsAppGateway $gateway,
        private WhatsAppMessageBuilder $messages,
    ) {}

    public function sendOtp(string $target, string $otp): void
    {
        if (app()->environment('testing') && blank(config('services.whatsapp_gateway.url'))) {
            return;
        }

        if (! $this->gateway->send($target, $this->messages->otpMessage($otp))) {
            throw new RuntimeException('Gagal mengirim OTP WhatsApp.');
        }
    }
}

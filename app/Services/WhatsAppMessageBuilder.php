<?php

namespace App\Services;

class WhatsAppMessageBuilder
{
    public function otpMessage(string $otp): string
    {
        $minutes = max(1, (int) ceil(((int) config('services.whatsapp.otp_expires_in', 300)) / 60));
        $appName = (string) config('app.name');

        return "Kode masuk {$appName}: {$otp}. Berlaku {$minutes} menit. Jangan bagikan kode ini kepada siapa pun.";
    }
}

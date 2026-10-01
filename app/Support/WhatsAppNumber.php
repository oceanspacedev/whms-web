<?php

namespace App\Support;

class WhatsAppNumber
{
    public static function normalize(?string $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '620')) {
            return '62'.substr($digits, 3);
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }

    public static function isValid(?string $value): bool
    {
        if (blank($value)) {
            return false;
        }

        $normalized = self::normalize($value);

        if (str_starts_with($normalized, '628')) {
            return preg_match('/^628\d{6,13}$/', $normalized) === 1;
        }

        if (str_starts_with($normalized, '62')) {
            return preg_match('/^62\d{7,14}$/', $normalized) === 1;
        }

        return preg_match('/^[1-9]\d{7,14}$/', $normalized) === 1;
    }

    public static function mask(string $value): string
    {
        $length = strlen($value);

        if ($length <= 4) {
            return $value;
        }

        return str_repeat('*', $length - 4).substr($value, -4);
    }
}

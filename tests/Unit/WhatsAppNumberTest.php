<?php

namespace Tests\Unit;

use App\Support\WhatsAppNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WhatsAppNumberTest extends TestCase
{
    #[DataProvider('normalizations')]
    public function test_normalizes_indonesian_numbers(string $input, string $expected): void
    {
        $this->assertSame($expected, WhatsAppNumber::normalize($input));
    }

    public function test_accepts_a_normalized_mobile_number(): void
    {
        $this->assertTrue(WhatsAppNumber::isValid('6281234567890'));
    }

    public function test_rejects_a_number_that_is_too_short(): void
    {
        $this->assertFalse(WhatsAppNumber::isValid(WhatsAppNumber::normalize('08123')));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function normalizations(): array
    {
        return [
            'local trunk zero' => ['081234567890', '6281234567890'],
            'plus and separators' => ['+62 812-3456-7890', '6281234567890'],
            'already international' => ['6281234567890', '6281234567890'],
            'missing country code' => ['81234567890', '6281234567890'],
            'country code with trunk zero' => ['62081234567890', '6281234567890'],
        ];
    }
}

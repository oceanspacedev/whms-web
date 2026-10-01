<?php

namespace Tests\Feature;

use App\Services\WhatsAppGateway;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppGatewayTest extends TestCase
{
    public function test_sends_a_text_message_to_the_gateway(): void
    {
        config([
            'services.whatsapp_gateway.url' => 'https://wag.test',
            'services.whatsapp_gateway.token' => 'test-token',
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'https://wag.test/api/v1/messages' => Http::response(['ok' => true], 202),
        ]);

        $sent = app(WhatsAppGateway::class)->send('081234567890', 'kode 123456');

        $this->assertTrue($sent);

        Http::assertSent(function ($request): bool {
            $idempotency = $request->header('Idempotency-Key');
            $idempotency = is_array($idempotency) ? ($idempotency[0] ?? '') : $idempotency;

            return $request->url() === 'https://wag.test/api/v1/messages'
                && $request['recipient']['type'] === 'phone'
                && $request['recipient']['value'] === '6281234567890'
                && $request['message']['text'] === 'kode 123456'
                && $request['client_reference'] === 'whms'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && str_starts_with((string) $idempotency, 'whms-');
        });
    }

    public function test_returns_false_when_the_gateway_url_is_missing(): void
    {
        config([
            'services.whatsapp_gateway.url' => null,
            'services.whatsapp_gateway.token' => 'test-token',
        ]);

        Http::fake();

        $sent = app(WhatsAppGateway::class)->send('6281234567890', 'kode');

        $this->assertFalse($sent);
        Http::assertNothingSent();
    }

    public function test_returns_false_when_the_gateway_rejects_the_message(): void
    {
        config([
            'services.whatsapp_gateway.url' => 'https://wag.test',
            'services.whatsapp_gateway.token' => 'test-token',
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'https://wag.test/api/v1/messages' => Http::response('failed', 500),
        ]);

        $sent = app(WhatsAppGateway::class)->send('6281234567890', 'kode');

        $this->assertFalse($sent);
    }
}

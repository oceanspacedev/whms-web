<?php

namespace Tests\Feature;

use App\Filament\Auth\Pages\PhoneLogin;
use App\Models\User;
use App\Models\WhatsappOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WhatsAppLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_login_page_renders_the_otp_form(): void
    {
        $response = $this->get('/phone-login');

        $response->assertOk();
        $response->assertSee('mky-auth-brand', false);
        $response->assertSee('Masuk dengan WhatsApp');
        $response->assertSee('Kirim OTP WhatsApp');
    }

    public function test_verified_panel_user_signs_in_with_whatsapp_otp(): void
    {
        $user = $this->panelUser([
            'whatsapp_number' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);

        $component = Livewire::test(PhoneLogin::class)
            ->fillForm([
                'whatsapp_number' => '081234567890',
            ])
            ->call('send')
            ->assertSet('awaitingOtp', true)
            ->assertHasNoFormErrors();

        $otp = WhatsappOtp::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(WhatsappOtp::PURPOSE_LOGIN, $otp->purpose);
        $this->assertNull($otp->verified_at);

        $otp->forceFill([
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinute(),
            'attempt_count' => 0,
            'verified_at' => null,
        ])->save();

        $component
            ->set('data.otp', '123456')
            ->call('verify')
            ->assertHasNoFormErrors()
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($otp->refresh()->verified_at);
    }

    public function test_unverified_whatsapp_number_is_rejected(): void
    {
        $this->panelUser([
            'whatsapp_number' => '6281234567890',
            'whatsapp_verified_at' => null,
        ]);

        Livewire::test(PhoneLogin::class)
            ->fillForm([
                'whatsapp_number' => '081234567890',
            ])
            ->call('send')
            ->assertHasFormErrors([
                'whatsapp_number' => 'Nomor WhatsApp belum terdaftar atau belum aktif.',
            ]);

        $this->assertSame(0, WhatsappOtp::query()->count());
        $this->assertGuest();
    }

    public function test_unknown_whatsapp_number_is_rejected(): void
    {
        Livewire::test(PhoneLogin::class)
            ->fillForm([
                'whatsapp_number' => '081234567890',
            ])
            ->call('send')
            ->assertHasFormErrors([
                'whatsapp_number' => 'Nomor WhatsApp belum terdaftar atau belum aktif.',
            ]);

        $this->assertGuest();
    }

    public function test_user_without_panel_access_cannot_request_whatsapp_otp(): void
    {
        User::factory()->create([
            'whatsapp_number' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);

        Livewire::test(PhoneLogin::class)
            ->fillForm([
                'whatsapp_number' => '081234567890',
            ])
            ->call('send')
            ->assertHasFormErrors([
                'whatsapp_number' => 'Nomor WhatsApp belum terdaftar atau belum aktif.',
            ]);

        $this->assertSame(0, WhatsappOtp::query()->count());
        $this->assertGuest();
    }

    public function test_invalid_whatsapp_number_is_rejected(): void
    {
        Livewire::test(PhoneLogin::class)
            ->fillForm([
                'whatsapp_number' => '12345',
            ])
            ->call('send')
            ->assertHasFormErrors([
                'whatsapp_number' => 'Nomor WhatsApp tidak valid.',
            ]);

        $this->assertGuest();
    }

    public function test_wrong_otp_is_rejected_and_counts_the_attempt(): void
    {
        $user = $this->panelUser([
            'whatsapp_number' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);

        $otp = WhatsappOtp::query()->create([
            'user_id' => $user->id,
            'whatsapp_number' => '6281234567890',
            'purpose' => WhatsappOtp::PURPOSE_LOGIN,
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinute(),
            'attempt_count' => 0,
        ]);

        Livewire::test(PhoneLogin::class)
            ->fillForm([
                'whatsapp_number' => '081234567890',
            ])
            ->set('awaitingOtp', true)
            ->set('data.otp', '000000')
            ->call('verify')
            ->assertHasFormErrors([
                'otp' => 'OTP tidak valid atau sudah kedaluwarsa.',
            ]);

        $this->assertGuest();
        $this->assertSame(1, $otp->refresh()->attempt_count);
    }

    public function test_expired_otp_is_rejected(): void
    {
        $this->freezeTime();

        $user = $this->panelUser([
            'whatsapp_number' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);

        WhatsappOtp::query()->create([
            'user_id' => $user->id,
            'whatsapp_number' => '6281234567890',
            'purpose' => WhatsappOtp::PURPOSE_LOGIN,
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->subSecond(),
            'attempt_count' => 0,
        ]);

        Livewire::test(PhoneLogin::class)
            ->fillForm([
                'whatsapp_number' => '081234567890',
            ])
            ->set('awaitingOtp', true)
            ->set('data.otp', '123456')
            ->call('verify')
            ->assertHasFormErrors([
                'otp' => 'OTP tidak valid atau sudah kedaluwarsa.',
            ]);

        $this->assertGuest();
    }

    public function test_gateway_failure_does_not_sign_the_user_in(): void
    {
        $this->freezeTime();

        config([
            'services.whatsapp_gateway.url' => 'https://wag.test',
            'services.whatsapp_gateway.token' => 'test-token',
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'https://wag.test/api/v1/messages' => Http::response('failed', 502),
        ]);

        $this->panelUser([
            'whatsapp_number' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);

        Livewire::test(PhoneLogin::class)
            ->fillForm([
                'whatsapp_number' => '081234567890',
            ])
            ->call('send')
            ->assertHasFormErrors([
                'whatsapp_number' => 'OTP belum bisa dikirim ke WhatsApp. Coba lagi sebentar lagi.',
            ])
            ->assertSet('awaitingOtp', false);

        $otp = WhatsappOtp::query()->first();

        $this->assertNotNull($otp);
        $this->assertTrue($otp->expires_at->lessThanOrEqualTo(now()));
        $this->assertGuest();
    }

    public function test_fourth_otp_request_for_the_same_number_is_rate_limited(): void
    {
        $this->panelUser([
            'whatsapp_number' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            Livewire::test(PhoneLogin::class)
                ->fillForm([
                    'whatsapp_number' => '081234567890',
                ])
                ->call('send')
                ->assertHasNoFormErrors();
        }

        Livewire::test(PhoneLogin::class)
            ->fillForm([
                'whatsapp_number' => '081234567890',
            ])
            ->call('send')
            ->assertHasFormErrors([
                'whatsapp_number' => 'Terlalu banyak permintaan OTP. Coba lagi sebentar lagi.',
            ]);
    }

    public function test_authenticated_user_is_redirected_away_from_phone_login(): void
    {
        $user = $this->panelUser();

        $this->actingAs($user);

        Livewire::test(PhoneLogin::class)
            ->assertRedirect('/admin');
    }

    public function test_accepts_various_valid_indonesian_phone_formats(): void
    {
        $formats = [
            '081812345',          // 9 digits
            '0811123456',         // 10 digits
            '081234567890',       // 12 digits
            '08123456789012',     // 14 digits
            '+62 813-3456-7890',  // formatted with symbols
            '+62081434567890',    // with 620 prefix
        ];

        foreach ($formats as $phone) {
            $user = $this->panelUser(['whatsapp_number' => $phone]);

            $this->assertNotNull($user->whatsapp_verified_at);
            $this->assertTrue(str_starts_with($user->whatsapp_number, '628'));
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function panelUser(array $attributes = []): User
    {
        $role = Role::findOrCreate('panel_user', 'web');
        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }
}

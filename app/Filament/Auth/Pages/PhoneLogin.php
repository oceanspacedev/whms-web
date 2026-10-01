<?php

namespace App\Filament\Auth\Pages;

use App\Filament\Auth\Concerns\InteractsWithWhatsAppLogin;
use App\Models\WhatsappOtp;
use App\Services\WhatsAppOtpService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @property-read Schema $form
 */
class PhoneLogin extends SimplePage
{
    use InteractsWithWhatsAppLogin;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public bool $awaitingOtp = false;

    public function boot(): void
    {
        if (Filament::getCurrentPanel() !== null) {
            return;
        }

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            $this->redirect(Filament::getUrl());
        }

        $this->maxWidth = 'full';
        $this->form->fill();
    }

    public function send(WhatsAppOtpService $otpService): void
    {
        $data = $this->form->getState();
        $ipKey = 'whatsapp-otp:ip:'.sha1((string) request()->ip());

        if (RateLimiter::tooManyAttempts($ipKey, 10)) {
            throw ValidationException::withMessages([
                'data.whatsapp_number' => 'Terlalu banyak permintaan OTP. Coba lagi sebentar lagi.',
            ]);
        }

        RateLimiter::hit($ipKey, 600);

        $number = $this->normalizeWhatsAppNumber($data['whatsapp_number'] ?? null);

        if (! $number) {
            throw ValidationException::withMessages([
                'data.whatsapp_number' => 'Nomor WhatsApp tidak valid.',
            ]);
        }

        $numberKey = 'whatsapp-otp:number:'.hash('sha256', $number);

        if (RateLimiter::tooManyAttempts($numberKey, 3)) {
            throw ValidationException::withMessages([
                'data.whatsapp_number' => 'Terlalu banyak permintaan OTP. Coba lagi sebentar lagi.',
            ]);
        }

        RateLimiter::hit($numberKey, 300);

        $user = $this->findEligibleWebUserByWhatsApp($number);

        if (! $user) {
            throw ValidationException::withMessages([
                'data.whatsapp_number' => $this->unavailableWhatsAppMessage(),
            ]);
        }

        try {
            $otpService->issue($user, $number, WhatsappOtp::PURPOSE_LOGIN);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'data.whatsapp_number' => 'OTP belum bisa dikirim ke WhatsApp. Coba lagi sebentar lagi.',
            ]);
        }

        $this->awaitingOtp = true;
        $this->form->fill([
            'whatsapp_number' => $number,
            'otp' => null,
        ]);
    }

    public function verify(WhatsAppOtpService $otpService): void
    {
        $data = $this->form->getState();
        $number = $this->normalizeWhatsAppNumber($data['whatsapp_number'] ?? null);
        $user = $number ? $this->findEligibleWebUserByWhatsApp($number) : null;

        if (! $user || ! $number) {
            throw ValidationException::withMessages([
                'data.whatsapp_number' => $this->unavailableWhatsAppMessage(),
            ]);
        }

        $otpRecord = $otpService->verify(
            $user,
            $number,
            WhatsappOtp::PURPOSE_LOGIN,
            (string) ($data['otp'] ?? ''),
        );

        if (! $otpRecord) {
            throw ValidationException::withMessages([
                'data.otp' => 'OTP tidak valid atau sudah kedaluwarsa.',
            ]);
        }

        Filament::auth()->login($user, true);
        session()->regenerate();

        $this->redirect(Filament::getUrl());
    }

    public function changePhone(): void
    {
        $this->awaitingOtp = false;
        $this->form->fill();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('whatsapp_number')
                ->label('Nomor WhatsApp')
                ->tel()
                ->required()
                ->maxLength(30)
                ->autocomplete('tel')
                ->disabled(fn (): bool => $this->awaitingOtp)
                ->dehydrated()
                ->autofocus(),
            TextInput::make('otp')
                ->label('Kode OTP')
                ->helperText('Masukkan 6 digit kode yang dikirim ke WhatsApp.')
                ->required()
                ->numeric()
                ->length(6)
                ->autocomplete('one-time-code')
                ->autofocus()
                ->visible(fn (): bool => $this->awaitingOtp),
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Masuk dengan WhatsApp';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Masuk dengan WhatsApp';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if ($this->awaitingOtp) {
            return 'Masukkan 6 digit kode yang dikirim ke WhatsApp.';
        }

        return 'Gunakan nomor WhatsApp yang sudah diverifikasi.';
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        $actions = [
            $this->awaitingOtp ? $this->getVerifyFormAction() : $this->getSendFormAction(),
        ];

        if ($this->awaitingOtp) {
            $actions[] = $this->getChangePhoneFormAction();
        }

        $actions[] = $this->getBackToLoginFormAction();

        return $actions;
    }

    protected function getSendFormAction(): Action
    {
        return Action::make('send')
            ->label('Kirim OTP WhatsApp')
            ->submit('send');
    }

    protected function getVerifyFormAction(): Action
    {
        return Action::make('verify')
            ->label('Verifikasi dan Masuk')
            ->submit('verify');
    }

    protected function getChangePhoneFormAction(): Action
    {
        return Action::make('changePhone')
            ->label('Ganti nomor atau kirim ulang OTP')
            ->color('gray')
            ->link()
            ->action('changePhone');
    }

    protected function getBackToLoginFormAction(): Action
    {
        return Action::make('backToLogin')
            ->label('Kembali ke halaman masuk')
            ->color('gray')
            ->link()
            ->url(fn (): string => filament()->getLoginUrl());
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    public function getView(): string
    {
        return 'filament.auth.phone-login';
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler(fn (): string => $this->awaitingOtp ? 'verify' : 'send')
                ->footer([
                    Actions::make($this->getFormActions())
                        ->alignment($this->getFormActionsAlignment())
                        ->fullWidth($this->hasFullWidthFormActions())
                        ->key('form-actions'),
                ]),
        ]);
    }
}

<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use App\Support\WhatsAppNumber;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('username')
                            ->label('Username')
                            ->maxLength(50)
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::lower(trim($state)) : null)
                            ->rules([
                                fn (TextInput $component): Closure => function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                                    if (blank($value)) {
                                        return;
                                    }

                                    $username = Str::lower(trim((string) $value));

                                    if (preg_match('/^[a-z0-9_-]{3,50}$/', $username) !== 1) {
                                        $fail('Username harus 3-50 karakter: huruf, angka, strip, atau garis bawah.');

                                        return;
                                    }

                                    $record = $component->getRecord();
                                    $taken = User::query()
                                        ->where('username', $username)
                                        ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                                        ->exists();

                                    if ($taken) {
                                        $fail('Username sudah dipakai.');
                                    }
                                },
                            ]),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        DateTimePicker::make('email_verified_at')
                            ->label('Email verified at')
                            ->nullable(),
                        TextInput::make('whatsapp_number')
                            ->label('WhatsApp number')
                            ->tel()
                            ->maxLength(30)
                            ->helperText('Contoh 081234567890. Isi waktu verifikasi agar user bisa masuk lewat WhatsApp.')
                            ->dehydrateStateUsing(function (?string $state): ?string {
                                if (blank($state)) {
                                    return null;
                                }

                                return WhatsAppNumber::normalize($state);
                            })
                            ->rules([
                                fn (TextInput $component): Closure => function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                                    if (blank($value)) {
                                        return;
                                    }

                                    $number = WhatsAppNumber::normalize((string) $value);

                                    if (! WhatsAppNumber::isValid($number)) {
                                        $fail('Nomor WhatsApp tidak valid.');

                                        return;
                                    }

                                    $record = $component->getRecord();
                                    $taken = User::query()
                                        ->where('whatsapp_number', $number)
                                        ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                                        ->exists();

                                    if ($taken) {
                                        $fail('Nomor WhatsApp sudah dipakai.');
                                    }
                                },
                            ]),
                        DateTimePicker::make('whatsapp_verified_at')
                            ->label('WhatsApp verified at')
                            ->nullable(),
                        TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),
                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ])
                    ->columns(2),
            ]);
    }
}

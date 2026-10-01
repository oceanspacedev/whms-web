<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => null,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'whatsapp_number' => null,
            'whatsapp_verified_at' => null,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withUsername(?string $username = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'username' => $username ?? fake()->unique()->userName(),
        ]);
    }

    public function withVerifiedWhatsApp(?string $number = '6281234567890'): static
    {
        return $this->state(fn (array $attributes): array => [
            'whatsapp_number' => $number,
            'whatsapp_verified_at' => now(),
        ]);
    }
}

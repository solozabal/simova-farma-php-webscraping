<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'role'              => 'subscriber',
            'status'            => 'lead',
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

    /** Admin user with access to Filament panel */
    public function admin(): static
    {
        return $this->state([
            'role'   => 'admin',
            'status' => 'active',
        ]);
    }

    /** Active subscriber */
    public function active(): static
    {
        return $this->state([
            'status'       => 'active',
            'activated_at' => now(),
        ]);
    }

    /** Pending subscriber (payment initiated) */
    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    /** Cancelled subscriber */
    public function cancelled(): static
    {
        return $this->state([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /** Test/internal user */
    public function test(): static
    {
        return $this->state(['status' => 'test']);
    }

    /** User with Telegram linked */
    public function withTelegram(?string $telegramId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'telegram_id'        => $telegramId ?? fake()->unique()->numerify('#########'),
            'telegram_username'  => fake()->userName(),
            'telegram_linked_at' => now(),
        ]);
    }

    /** Active subscriber who can receive Telegram messages */
    public function receivingTelegram(): static
    {
        return $this->active()->withTelegram();
    }
}

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
            'name'               => fake()->name(),
            'email'              => fake()->unique()->safeEmail(),
            'email_verified_at'  => now(),
            'password'           => static::$password ??= Hash::make('password'),
            'remember_token'     => Str::random(10),
            'role'               => 'subscriber',
            'status'             => 'lead',
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

    /** Admin user with access to the Filament panel. */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role'   => 'admin',
            'status' => 'active',
        ]);
    }

    /** Active subscriber. */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => 'active',
            'activated_at' => now(),
        ]);
    }

    /** Pending subscriber (payment started, awaiting confirmation). */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /** Cancelled subscriber. */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /** Test/whitelist user. */
    public function test(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'test',
        ]);
    }

    /** User with a linked Telegram account. */
    public function withTelegram(): static
    {
        return $this->state(fn (array $attributes) => [
            'telegram_id'       => (string) fake()->numerify('##########'),
            'telegram_username' => fake()->userName(),
            'telegram_linked_at'=> now(),
        ]);
    }

    /** User with a valid Telegram link token. */
    public function withTelegramLinkToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'telegram_link_token'            => Str::random(40),
            'telegram_link_token_expires_at' => now()->addHours(24),
        ]);
    }

    /** Active subscriber with linked Telegram (eligible for messages). */
    public function receivingTelegram(): static
    {
        return $this->active()->withTelegram();
    }
}

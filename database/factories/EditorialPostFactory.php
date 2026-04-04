<?php

namespace Database\Factories;

use App\Models\EditorialPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EditorialPost>
 */
class EditorialPostFactory extends Factory
{
    protected $model = EditorialPost::class;

    public function definition(): array
    {
        return [
            'title'            => fake()->sentence(),
            'content'          => fake()->paragraphs(2, true),
            'content_fallback' => fake()->paragraph(),
            'slot'             => fake()->randomElement(['morning', 'afternoon']),
            'scheduled_at'     => now()->addHour(),
            'status'           => 'draft',
            'recipients_count' => 0,
        ];
    }

    /** Post approved and scheduled to be sent in the past (ready to send now). */
    public function readyToSend(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => 'approved',
            'scheduled_at' => now()->subMinute(),
        ]);
    }

    /** Post already sent. */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'           => 'sent',
            'sent_at'          => now()->subHour(),
            'recipients_count' => fake()->numberBetween(1, 100),
        ]);
    }

    /** Morning slot (09:10). */
    public function morning(): static
    {
        return $this->state(fn (array $attributes) => [
            'slot' => 'morning',
        ]);
    }

    /** Afternoon slot (17:40). */
    public function afternoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'slot' => 'afternoon',
        ]);
    }
}

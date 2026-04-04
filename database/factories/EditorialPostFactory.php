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
            'title'            => fake()->sentence(5),
            'content'          => fake()->paragraphs(2, true),
            'content_fallback' => fake()->paragraph(),
            'slot'             => fake()->randomElement(['morning', 'afternoon']),
            'scheduled_at'     => now()->subMinutes(5),
            'status'           => 'draft',
            'sent_at'          => null,
            'recipients_count' => 0,
            'approved_by'      => null,
            'approved_at'      => null,
            'article_id'       => null,
        ];
    }

    /** Slot da manhã (09:10) */
    public function morning(): static
    {
        return $this->state(['slot' => 'morning']);
    }

    /** Slot da tarde (17:40) */
    public function afternoon(): static
    {
        return $this->state(['slot' => 'afternoon']);
    }

    /** Post aprovado e pronto para envio */
    public function approved(): static
    {
        return $this->state([
            'status'       => 'approved',
            'approved_at'  => now()->subMinutes(10),
        ]);
    }

    /** Post aprovado com scheduled_at no passado — pronto para envio imediato */
    public function readyToSend(): static
    {
        return $this->approved()->state([
            'scheduled_at' => now()->subMinutes(1),
        ]);
    }

    /** Post agendado para o futuro (não enviável ainda) */
    public function scheduledFuture(): static
    {
        return $this->approved()->state([
            'scheduled_at' => now()->addHours(2),
        ]);
    }

    /** Post já enviado */
    public function sent(): static
    {
        return $this->state([
            'status'           => 'sent',
            'sent_at'          => now()->subMinutes(30),
            'recipients_count' => fake()->numberBetween(1, 50),
        ]);
    }

    /** Post cancelado */
    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }
}

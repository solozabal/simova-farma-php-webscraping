<?php

namespace Database\Factories;

use App\Models\SystemLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SystemLog>
 */
class SystemLogFactory extends Factory
{
    protected $model = SystemLog::class;

    public function definition(): array
    {
        return [
            'level'   => fake()->randomElement(['debug', 'info', 'warning', 'error']),
            'channel' => fake()->randomElement(['telegram', 'mercadopago', 'scraper', 'scheduler', 'app']),
            'message' => fake()->sentence(),
            'context' => null,
            'user_id' => null,
        ];
    }

    public function info(): static
    {
        return $this->state(fn (array $attributes) => ['level' => 'info']);
    }

    public function warning(): static
    {
        return $this->state(fn (array $attributes) => ['level' => 'warning']);
    }

    public function error(): static
    {
        return $this->state(fn (array $attributes) => ['level' => 'error']);
    }

    public function telegram(): static
    {
        return $this->state(fn (array $attributes) => ['channel' => 'telegram']);
    }

    public function mercadopago(): static
    {
        return $this->state(fn (array $attributes) => ['channel' => 'mercadopago']);
    }
}

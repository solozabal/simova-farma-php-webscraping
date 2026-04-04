<?php

namespace Database\Factories;

use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlogPost>
 */
class BlogPostFactory extends Factory
{
    protected $model = BlogPost::class;

    public function definition(): array
    {
        $title = fake()->sentence();

        return [
            'title'            => $title,
            'slug'             => Str::slug($title) . '-' . fake()->unique()->numberBetween(1000, 9999),
            'content'          => fake()->paragraphs(5, true),
            'excerpt'          => fake()->paragraph(),
            'meta_title'       => $title,
            'meta_description' => fake()->sentence(),
            'category'         => fake()->randomElement(['gestao', 'marketing', 'vendas', 'operacao']),
            'tags'             => ['farmácia', 'gestão'],
            'featured_image'   => null,
            'status'           => 'draft',
            'published_at'     => null,
        ];
    }

    /** Published blog post. */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ]);
    }

    /** Archived blog post. */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }
}

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
        $title = fake()->unique()->sentence(5);

        return [
            'title'            => $title,
            'slug'             => Str::slug($title) . '-' . fake()->numberBetween(1000, 9999),
            'content'          => fake()->paragraphs(5, true),
            'excerpt'          => fake()->paragraph(),
            'meta_title'       => $title,
            'meta_description' => fake()->sentence(20),
            'category'         => fake()->randomElement(['gestao', 'marketing', 'vendas', 'operacao']),
            'tags'             => ['farmacia', 'varejo'],
            'featured_image'   => null,
            'author_id'        => null,
            'status'           => 'draft',
            'published_at'     => null,
        ];
    }

    /** Post publicado */
    public function published(): static
    {
        return $this->state([
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ]);
    }

    /** Post arquivado */
    public function archived(): static
    {
        return $this->state(['status' => 'archived']);
    }
}

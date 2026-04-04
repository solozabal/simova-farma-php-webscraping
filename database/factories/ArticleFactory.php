<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = fake()->sentence();
        $url   = fake()->url();

        return [
            'hash'         => Article::makeHash($title, $url),
            'title'        => $title,
            'content'      => fake()->paragraph(),
            'url'          => $url,
            'source_key'   => fake()->randomElement(['anvisa', 'uol_economia', 'g1_saude', 'cff', 'ibge']),
            'source_label' => fake()->company(),
            'category'     => fake()->randomElement(['regulamentacao', 'varejo', 'tecnologia', 'marketing', 'geral']),
            'language'     => 'pt',
            'score'        => fake()->numberBetween(0, 10),
            'impact_label' => fake()->randomElement(['Alto Impacto', 'Médio Impacto', 'Baixo Impacto']),
            'insight'      => fake()->sentence(),
            'alert_sent'   => false,
            'digest_sent'  => false,
            'blog_published' => false,
            'published_at' => now(),
        ];
    }

    /** Article eligible for daily digest (score >= 5, not yet sent, published today). */
    public function forDigest(): static
    {
        return $this->state(fn (array $attributes) => [
            'score'        => fake()->numberBetween(5, 7),
            'digest_sent'  => false,
            'published_at' => now(),
        ]);
    }

    /** High-score article eligible for immediate alert (score >= 8). */
    public function forAlert(): static
    {
        return $this->state(fn (array $attributes) => [
            'score'      => fake()->numberBetween(8, 10),
            'alert_sent' => false,
        ]);
    }

    /** Article already sent in digest. */
    public function digestSent(): static
    {
        return $this->state(fn (array $attributes) => [
            'digest_sent' => true,
        ]);
    }
}

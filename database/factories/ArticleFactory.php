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
        $title = fake()->sentence(6);
        $url   = fake()->unique()->url();

        return [
            'hash'          => Article::makeHash($title, $url),
            'title'         => $title,
            'content'       => fake()->paragraph(3),
            'url'           => $url,
            'source_key'    => fake()->randomElement(['anvisa', 'uol_economia', 'folha', 'gnews']),
            'source_label'  => fake()->company(),
            'category'      => fake()->randomElement(['regulamentacao', 'varejo', 'tecnologia', 'marketing', 'geral']),
            'language'      => 'pt',
            'score'         => fake()->numberBetween(0, 10),
            'impact_label'  => fake()->randomElement(['ALTO IMPACTO', 'REGULATÓRIO', 'VAREJO', 'TECNOLOGIA']),
            'insight'       => fake()->sentence(10),
            'alert_sent'    => false,
            'digest_sent'   => false,
            'blog_published'=> false,
            'published_at'  => now()->subHours(rand(1, 6)),
        ];
    }

    /** Score alto (>= 8) — elegível para alerta imediato */
    public function highScore(): static
    {
        return $this->state(['score' => fake()->numberBetween(8, 10)]);
    }

    /** Score médio (5–7) — elegível para digest diário */
    public function digestScore(): static
    {
        return $this->state(['score' => fake()->numberBetween(5, 7)]);
    }

    /** Score baixo (< 5) — apenas armazenado */
    public function lowScore(): static
    {
        return $this->state(['score' => fake()->numberBetween(0, 4)]);
    }

    /** Elegível para o digest diário (score >= 5, não enviado, publicado hoje) */
    public function digestEligible(): static
    {
        return $this->state([
            'score'        => fake()->numberBetween(5, 10),
            'digest_sent'  => false,
            'published_at' => today()->addHours(6),
        ]);
    }

    /** Já enviado no digest */
    public function digestSent(): static
    {
        return $this->state(['digest_sent' => true]);
    }

    /** Elegível para alerta imediato (score >= 8, não enviado) */
    public function alertEligible(): static
    {
        return $this->state([
            'score'      => fake()->numberBetween(8, 10),
            'alert_sent' => false,
        ]);
    }
}

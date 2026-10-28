<?php

namespace Database\Factories;

use App\Modules\Content\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = rtrim(fake()->sentence(6), '.');

        return [
            'title' => $title,
            'locale' => 'en',
            'excerpt' => fake()->sentence(18),
            'content' => '<p>'.implode('</p><p>', fake()->paragraphs(4)).'</p>',
            'status' => 'draft',
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => 'published', 'published_at' => now()->subDay()]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => ['status' => 'published', 'published_at' => now()->addDay()]);
    }
}

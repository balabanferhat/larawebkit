<?php

namespace Database\Factories;

use App\Modules\Content\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $title = rtrim(fake()->sentence(6), '.');

        return [
            'title' => $title,
            'locale' => 'en',
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

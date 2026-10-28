<?php

namespace App\Modules\Content;

use App\Modules\Content\Models\Menu;
use App\Modules\Content\Models\Page;

/**
 * Idempotent starter content: header/footer menus and privacy / cookie
 * policy stubs (clearly marked as templates that must be reviewed).
 */
class DefaultContent
{
    public function seed(): void
    {
        $locale = (string) setting('general.default_locale', 'en');

        $privacy = $this->page('privacy-policy', __('larawebkit-privacy.pages.privacy_title', [], $locale), __('larawebkit-privacy.pages.privacy_body', [], $locale), $locale);
        $cookies = $this->page('cookie-policy', __('larawebkit-privacy.pages.cookie_title', [], $locale), __('larawebkit-privacy.pages.cookie_body', [], $locale), $locale);

        $header = Menu::firstOrCreate(['location' => 'header'], ['name' => 'Header']);
        if (! $header->items()->exists()) {
            $header->items()->create(['label' => __('larawebkit-front.blog', [], $locale), 'url' => '/blog', 'sort_order' => 1]);
        }

        $footer = Menu::firstOrCreate(['location' => 'footer'], ['name' => 'Footer']);
        if (! $footer->items()->exists()) {
            $footer->items()->create(['label' => $privacy->title, 'linkable_type' => $privacy->getMorphClass(), 'linkable_id' => $privacy->id, 'sort_order' => 1]);
            $footer->items()->create(['label' => $cookies->title, 'linkable_type' => $cookies->getMorphClass(), 'linkable_id' => $cookies->id, 'sort_order' => 2]);
        }
    }

    private function page(string $slug, string $title, string $body, string $locale): Page
    {
        return Page::withTrashed()->firstOrCreate(
            ['slug' => $slug, 'locale' => $locale, 'parent_id' => null],
            ['title' => $title, 'content' => $body, 'template' => 'legal', 'status' => 'published', 'published_at' => now(), 'noindex' => true],
        );
    }
}

<?php

namespace App\Modules\Content;

use App\Modules\Content\Models\Menu;
use App\Modules\Content\Models\Page;
use App\Modules\Core\ModuleRegistry;
use App\Modules\Forms\Models\Form;

/**
 * Idempotent starter content: header/footer menus and privacy / cookie
 * policy stubs (clearly marked as templates that must be reviewed).
 */
class DefaultContent
{
    public function seed(): void
    {
        $locale = (string) setting('general.default_locale', 'en');

        // Policy stubs in every active language, linked as translations of each other (hreflang).
        $locales = array_values(array_unique([$locale, ...array_map('strval', (array) setting('general.active_locales', [$locale]))]));
        $pages = [];
        foreach (['privacy-policy' => 'privacy', 'cookie-policy' => 'cookie'] as $slug => $key) {
            foreach ($locales as $l) {
                $pages[$slug][$l] = $this->page($slug, __("larawebkit-privacy.pages.{$key}_title", [], $l), __("larawebkit-privacy.pages.{$key}_body", [], $l), $l);
            }
            $group = $pages[$slug][$locale]->translation_group;
            foreach ($pages[$slug] as $page) {
                if ($page->translation_group !== $group) {
                    $page->forceFill(['translation_group' => $group])->saveQuietly();
                }
            }
        }
        $privacy = $pages['privacy-policy'][$locale];
        $cookies = $pages['cookie-policy'][$locale];

        $this->contactForm($locale);

        $header = Menu::firstOrCreate(['location' => 'header'], ['name' => 'Header']);
        if (! $header->items()->exists()) {
            $header->items()->create(['label' => __('larawebkit-front.blog', [], $locale), 'url' => blog_path('', $locale), 'sort_order' => 1]);
        }

        $footer = Menu::firstOrCreate(['location' => 'footer'], ['name' => 'Footer']);
        if (! $footer->items()->exists()) {
            $footer->items()->create(['label' => $privacy->title, 'linkable_type' => $privacy->getMorphClass(), 'linkable_id' => $privacy->id, 'sort_order' => 1]);
            $footer->items()->create(['label' => $cookies->title, 'linkable_type' => $cookies->getMorphClass(), 'linkable_id' => $cookies->id, 'sort_order' => 2]);
        }
    }

    /** The contact form (Forms module), with labels in the site language. */
    private function contactForm(string $locale): void
    {
        if (! ModuleRegistry::enabled('forms') || Form::where('slug', 'contact')->exists()) {
            return;
        }
        $label = fn (string $key) => __("larawebkit-forms.default.fields.$key", [], $locale);
        Form::create([
            'name' => __('larawebkit-forms.default.name', [], $locale),
            'slug' => 'contact',
            'is_active' => true,
            'fields' => [
                ['key' => 'name', 'type' => 'text', 'label' => $label('name'), 'required' => true, 'width' => 'half', 'max' => 120],
                ['key' => 'email', 'type' => 'email', 'label' => $label('email'), 'required' => true, 'width' => 'half'],
                ['key' => 'subject', 'type' => 'text', 'label' => $label('subject'), 'required' => false, 'width' => 'full', 'max' => 200],
                ['key' => 'message', 'type' => 'textarea', 'label' => $label('message'), 'required' => true, 'width' => 'full', 'max' => 5000],
                ['key' => 'consent', 'type' => 'consent', 'label' => $label('consent'), 'consent_version' => 1, 'link_url' => localized_path('/privacy-policy', $locale)],
            ],
        ]);
    }

    private function page(string $slug, string $title, string $body, string $locale): Page
    {
        return Page::withTrashed()->firstOrCreate(
            ['slug' => $slug, 'locale' => $locale, 'parent_id' => null],
            ['title' => $title, 'content' => $body, 'template' => 'legal', 'status' => 'published', 'published_at' => now(), 'noindex' => true],
        );
    }
}

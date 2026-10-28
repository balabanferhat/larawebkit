<?php

namespace App\Modules\Dashboard\Widgets;

use App\Modules\Content\Filament\Resources\Pages\PageResource;
use App\Modules\Content\Filament\Resources\Posts\PostResource;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use Filament\Widgets\Widget;

/** Drafts, pending reviews, scheduled items and published content with weak scores. */
class ContentAttention extends Widget
{
    protected static ?int $sort = 4;

    protected string $view = 'admin.widgets.content-attention';

    public static function canView(): bool
    {
        return auth()->user()?->can('view dashboard') ?? false;
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $posts = Post::query()->when(! $user?->can('manage posts'), fn ($q) => $q->where('author_id', $user?->id));
        $low = fn ($q) => $q->where(fn ($w) => $w->where('seo_score', '<', 50)->orWhere('human_score', '<', 45));

        return [
            'pending' => (clone $posts)->where('status', 'pending')->latest('updated_at')->limit(5)->get(),
            'drafts' => (clone $posts)->where('status', 'draft')->latest('updated_at')->limit(5)->get(),
            'scheduled' => (clone $posts)->where('status', 'published')->where('published_at', '>', now())->orderBy('published_at')->limit(5)->get(),
            'weak' => $low((clone $posts)->published())->orderBy('seo_score')->limit(5)->get()
                ->concat($user?->can('manage pages') ? $low(Page::published()->where('template', '!=', 'legal'))->limit(3)->get() : collect()),
            'editUrl' => fn ($item) => $item instanceof Post ? PostResource::getUrl('edit', ['record' => $item]) : PageResource::getUrl('edit', ['record' => $item]),
        ];
    }
}

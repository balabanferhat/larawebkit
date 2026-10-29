<?php

namespace App\Modules\Blocks\Types;

use App\Modules\Blocks\Block;
use App\Modules\Blocks\BlockFields as F;
use App\Modules\Content\Models\Category;
use App\Modules\Content\Models\Post;
use App\Modules\Core\ModuleRegistry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class LatestPosts extends Block
{
    public static function key(): string
    {
        return 'latest_posts';
    }

    public static function icon(): Heroicon
    {
        return Heroicon::OutlinedNewspaper;
    }

    public static function variants(): array
    {
        return ['cards', 'list', 'featured'];
    }

    public static function fields(): array
    {
        return [
            F::eyebrow(),
            F::heading(),
            Select::make('count')->label(__('larawebkit-blocks.fields.count'))->options([3 => 3, 4 => 4, 6 => 6, 8 => 8, 9 => 9])->default(3)->native(false)->selectablePlaceholder(false),
            Select::make('category_id')->label(__('larawebkit-content.categories.singular'))->options(fn () => Category::orderBy('name')->pluck('name', 'id'))->searchable()->placeholder(__('larawebkit-blocks.fields.all_categories')),
            TextInput::make('link_label')->label(__('larawebkit-blocks.fields.link_label'))->maxLength(40)->placeholder(__('larawebkit-front.blog')),
        ];
    }

    public static function prepare(array $data): array
    {
        if (! ModuleRegistry::enabled('content')) {
            return ['posts' => collect()];
        }
        $posts = Post::published()->where('locale', app()->getLocale())
            ->when($data['category_id'] ?? null, fn ($q, $id) => $q->whereHas('categories', fn ($c) => $c->whereKey($id)))
            ->with('featuredMedia', 'author', 'categories')->latest('published_at')
            ->limit(max(1, min(12, (int) ($data['count'] ?? 3))))->get();

        return ['posts' => $posts, 'category' => ($id = $data['category_id'] ?? null) ? Category::find($id) : null];
    }
}

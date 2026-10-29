<?php

namespace App\Modules\Events\Blocks;

use App\Modules\Blocks\Block;
use App\Modules\Blocks\BlockFields as F;
use App\Modules\Events\Events;
use App\Modules\Events\Models\Event;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

/** The next events of the page's language, with a link to the listing. Hidden while Events is off. */
class UpcomingEvents extends Block
{
    public static function key(): string
    {
        return 'upcoming_events';
    }

    public static function icon(): Heroicon
    {
        return Heroicon::OutlinedCalendarDays;
    }

    public static function variants(): array
    {
        return ['cards', 'list'];
    }

    public static function fields(): array
    {
        return [
            F::eyebrow(),
            F::heading(),
            Select::make('count')->label(__('larawebkit-blocks.fields.count'))->options([2 => 2, 3 => 3, 4 => 4, 6 => 6])->default(3)->native(false)->selectablePlaceholder(false),
            TextInput::make('link_label')->label(__('larawebkit-blocks.fields.link_label'))->maxLength(40)->placeholder(__('larawebkit-events.upcoming')),
        ];
    }

    public static function prepare(array $data): array
    {
        if (! Events::enabled()) {
            return ['events' => collect(), 'enabled' => false];
        }
        $events = Event::listed()->upcoming()->where('locale', app()->getLocale())->with('featuredMedia')->orderBy('starts_at')
            ->limit(max(1, min(12, (int) ($data['count'] ?? 3))))->get();

        return ['events' => $events, 'enabled' => true];
    }
}

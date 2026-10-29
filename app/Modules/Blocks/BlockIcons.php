<?php

namespace App\Modules\Blocks;

use App\Modules\Themes\Support\Icons;
use Illuminate\Support\HtmlString;

/** A curated set of Heroicons for feature items (outline style). */
final class BlockIcons
{
    public const ICONS = [
        'sparkles', 'bolt', 'rocket-launch', 'shield-check', 'lock-closed', 'chart-bar', 'chart-pie', 'cog-6-tooth',
        'wrench-screwdriver', 'light-bulb', 'heart', 'star', 'check-badge', 'clock', 'calendar-days', 'chat-bubble-left-right',
        'users', 'user-group', 'globe-alt', 'map-pin', 'device-phone-mobile', 'computer-desktop', 'cloud', 'cpu-chip',
        'code-bracket', 'paint-brush', 'pencil-square', 'document-text', 'academic-cap', 'book-open', 'briefcase', 'banknotes',
        'currency-dollar', 'shopping-bag', 'truck', 'gift', 'trophy', 'hand-thumb-up', 'face-smile', 'megaphone',
        'microphone', 'video-camera', 'camera', 'photo', 'musical-note', 'puzzle-piece', 'scale', 'sun',
    ];

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::ICONS)->mapWithKeys(fn ($icon) => [$icon => str_replace('-', ' ', $icon)])->all();
    }

    public static function render(?string $name): HtmlString
    {
        if (! $name) {
            return new HtmlString('');
        }

        return in_array($name, self::ICONS, true) ? Icons::ui('o-'.$name, 'lwk-icon') : new HtmlString('');
    }
}

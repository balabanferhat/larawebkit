<?php

namespace App\Modules\Blocks;

use App\Modules\Core\ModuleRegistry;
use Closure;

/**
 * Forms that the "form" block can embed. The contact form is built in; other
 * modules (the v0.2-B form builder) register theirs: key => [label, view].
 */
final class BlockForms
{
    /** @var array<string, array{label: Closure|string, view: string}> */
    private static array $forms = [];

    public static function register(string $key, Closure|string $label, string $view): void
    {
        self::$forms[$key] = ['label' => $label, 'view' => $view];
    }

    /** @return array<string, array{label: Closure|string, view: string}> */
    public static function all(): array
    {
        $builtIn = ModuleRegistry::enabled('contact')
            ? ['contact' => ['label' => fn () => __('larawebkit-front.contact.title'), 'view' => 'components.lwk.forms.contact']]
            : [];

        return $builtIn + self::$forms;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::all())->map(fn ($f) => value($f['label']))->all();
    }

    public static function view(?string $key): ?string
    {
        return self::all()[$key ?? '']['view'] ?? null;
    }
}

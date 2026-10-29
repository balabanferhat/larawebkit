<?php

namespace App\Modules\Blocks;

use Closure;

/**
 * Forms that the "form" block can embed: key => [label, view]. Modules add theirs with
 * register() or, for a list that lives in the database (the Forms module), resolver().
 */
final class BlockForms
{
    /** @var array<string, array{label: Closure|string, view: string}> */
    private static array $forms = [];

    /** @var list<Closure> */
    private static array $resolvers = [];

    public static function register(string $key, Closure|string $label, string $view): void
    {
        self::$forms[$key] = ['label' => $label, 'view' => $view];
    }

    /** @param  Closure(): array<string, array{label: string, view: string}>  $resolver */
    public static function resolver(Closure $resolver): void
    {
        self::$resolvers[] = $resolver;
    }

    /** @return array<string, array{label: Closure|string, view: string}> */
    public static function all(): array
    {
        $all = self::$forms;
        foreach (self::$resolvers as $resolver) {
            $all += (array) $resolver();
        }

        return $all;
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

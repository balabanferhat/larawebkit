<?php

namespace App\Modules\Themes;

use Illuminate\Foundation\Vite;
use Illuminate\Support\HtmlString;
use Throwable;

class ThemeAssets
{
    public function __construct(private ThemeManager $themes, private Vite $vite) {}

    public function styles(): HtmlString
    {
        $built = is_file(public_path('build/manifest.json')) || is_file(public_path('hot'));
        if ($built) {
            try {
                return new HtmlString((string) $this->vite->__invoke([$this->themes->cssEntry(), 'resources/js/site.js']));
            } catch (Throwable) {
                // fall through to the inline fallback
            }
        }
        $fallback = resource_path('css/themes/base.css');

        return new HtmlString('<style>'.(is_file($fallback) ? file_get_contents($fallback) : '').'</style>');
    }
}

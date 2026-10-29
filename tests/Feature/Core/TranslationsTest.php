<?php

use Illuminate\Support\Arr;

it('ships every larawebkit translation key in both en and tr', function () {
    foreach (glob(lang_path('en/larawebkit*.php')) as $file) {
        $name = basename($file);
        $tr = lang_path("tr/$name");
        expect(file_exists($tr))->toBeTrue("missing tr/$name");

        $enKeys = array_keys(Arr::dot(require $file));
        $trKeys = array_keys(Arr::dot(require $tr));
        sort($enKeys);
        sort($trKeys);
        expect(array_values(array_diff($enKeys, $trKeys)))->toBe([], "keys missing in tr/$name")
            ->and(array_values(array_diff($trKeys, $enKeys)))->toBe([], "extra keys in tr/$name");
    }
});

it('has a label for every setting in both locales', function () {
    foreach (array_keys(config('larawebkit-settings')) as $key) {
        foreach (['en', 'tr'] as $locale) {
            expect(__("larawebkit-settings.keys.$key", [], $locale))->not->toBe("larawebkit-settings.keys.$key", "$locale: $key");
        }
    }
});

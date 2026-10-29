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

it('translates Laravel\'s validation, auth, password and pagination messages to Turkish', function () {
    foreach (['validation', 'auth', 'passwords', 'pagination'] as $file) {
        $en = array_keys(array_filter(Arr::dot(require base_path("vendor/laravel/framework/src/Illuminate/Translation/lang/en/$file.php")), fn ($v) => $v !== []));
        $tr = array_keys(Arr::dot(require lang_path("tr/$file.php")));
        expect(array_values(array_diff($en, $tr)))->toBe([], "keys missing in tr/$file.php");
    }
    app()->setLocale('tr');
    expect(validator(['email' => 'x'], ['email' => 'email'])->errors()->first('email'))->toBe('e-posta alanı geçerli bir e-posta adresi olmalıdır.');
});

it('ships every theme translation in both en and tr', function () {
    foreach (glob(base_path('themes/*/lang/en/*.php')) as $file) {
        $tr = str_replace('/lang/en/', '/lang/tr/', $file);
        expect(file_exists($tr))->toBeTrue("missing $tr");
        $enKeys = array_keys(Arr::dot(require $file));
        $trKeys = array_keys(Arr::dot(require $tr));
        expect(array_values(array_diff($enKeys, $trKeys)))->toBe([], "keys missing in $tr")
            ->and(array_values(array_diff($trKeys, $enKeys)))->toBe([], "extra keys in $tr");
    }
});

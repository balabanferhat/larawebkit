<?php

it('lists every larawebkit environment variable in .env.example', function () {
    $example = file_get_contents(base_path('.env.example'));
    $files = array_merge(glob(config_path('larawebkit*.php')), [config_path('services.php')]);

    foreach ($files as $file) {
        preg_match_all("/env\\('([A-Z0-9_]+)'/", file_get_contents($file), $m);
        foreach ($m[1] as $var) {
            expect($example)->toMatch("/^$var=/m", "$var from ".basename($file).' is missing in .env.example');
        }
    }
});

it('ships no real secrets in .env.example', function () {
    preg_match_all('/^([A-Z0-9_]*(KEY|SECRET|TOKEN|PASSWORD)[A-Z0-9_]*)=(.*)$/m', file_get_contents(base_path('.env.example')), $m, PREG_SET_ORDER);

    foreach ($m as [, $name, , $value]) {
        expect(in_array(trim($value), ['', 'null', '""'], true))->toBeTrue("$name must be empty in .env.example");
    }
});

it('keeps modules decoupled: each has its own service provider or plugin', function () {
    foreach (config('larawebkit.modules') as $name => $module) {
        expect($module['provider'] || $module['plugin'])->toBeTrue($name);
        foreach (array_filter($module) as $class) {
            expect(class_exists($class))->toBeTrue($class);
        }
    }
});

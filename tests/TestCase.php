<?php

namespace Tests;

use App\Modules\Settings\SettingsRepository;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('public');
        Storage::fake('local');
        app(SettingsRepository::class)->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Release the previous application (and any image bitmaps it referenced) before the next
        // test boots a new one, so memory does not pile up across the suite.
        gc_collect_cycles();
        // LWK_MEMLOG=/path/to/file logs memory per test (to find what grows across the suite).
        if (getenv('LWK_MEMLOG')) {
            file_put_contents(getenv('LWK_MEMLOG'), sprintf("%s %d %d\n", $this->name(), memory_get_usage(true) >> 20, memory_get_usage() >> 20), FILE_APPEND);
        }
    }
}

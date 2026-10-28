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
}

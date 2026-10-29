<?php

use App\Modules;

/*
|--------------------------------------------------------------------------
| larawebkit
|--------------------------------------------------------------------------
|
| Every feature is a module with its own service provider and (optionally)
| a Filament plugin that registers its admin resources, pages and widgets.
| Remove a line here to drop a module from a project; see docs/architecture.md.
|
*/

return [

    'modules' => [
        'settings' => ['provider' => Modules\Settings\SettingsServiceProvider::class, 'plugin' => Modules\Settings\SettingsPlugin::class],
        'security' => ['provider' => Modules\Security\SecurityServiceProvider::class, 'plugin' => Modules\Security\SecurityPlugin::class],
        'logs' => ['provider' => Modules\Logs\LogsServiceProvider::class, 'plugin' => Modules\Logs\LogsPlugin::class],
        'media' => ['provider' => Modules\Media\MediaServiceProvider::class, 'plugin' => Modules\Media\MediaPlugin::class],
        'content' => ['provider' => Modules\Content\ContentServiceProvider::class, 'plugin' => Modules\Content\ContentPlugin::class],
        'editor' => ['provider' => Modules\Editor\EditorServiceProvider::class, 'plugin' => null],
        'ai' => ['provider' => Modules\Ai\AiServiceProvider::class, 'plugin' => Modules\Ai\AiPlugin::class],
        'blocks' => ['provider' => Modules\Blocks\BlocksServiceProvider::class, 'plugin' => null],
        'themes' => ['provider' => Modules\Themes\ThemesServiceProvider::class, 'plugin' => Modules\Themes\ThemesPlugin::class],
        'seo' => ['provider' => Modules\Seo\SeoServiceProvider::class, 'plugin' => Modules\Seo\SeoPlugin::class],
        'geo' => ['provider' => Modules\Geo\GeoServiceProvider::class, 'plugin' => null],
        'privacy' => ['provider' => Modules\Privacy\PrivacyServiceProvider::class, 'plugin' => null],
        'members' => ['provider' => Modules\Members\MembersServiceProvider::class, 'plugin' => Modules\Members\MembersPlugin::class],
        'forms' => ['provider' => Modules\Forms\FormsServiceProvider::class, 'plugin' => Modules\Forms\FormsPlugin::class],
        'newsletter' => ['provider' => Modules\Newsletter\NewsletterServiceProvider::class, 'plugin' => Modules\Newsletter\NewsletterPlugin::class],
        'events' => ['provider' => Modules\Events\EventsServiceProvider::class, 'plugin' => Modules\Events\EventsPlugin::class],
        'backup' => ['provider' => Modules\Backup\BackupServiceProvider::class, 'plugin' => Modules\Backup\BackupPlugin::class],
        'import' => ['provider' => Modules\Import\ImportServiceProvider::class, 'plugin' => null],
        'dashboard' => ['provider' => null, 'plugin' => Modules\Dashboard\DashboardPlugin::class],
    ],

    // Locales shipped with the kit. The active subset is a setting (general.active_locales).
    'locales' => [
        'en' => 'English',
        'tr' => 'Türkçe',
    ],

    'admin_path' => env('ADMIN_PATH', 'admin'),

    // Signed download links (lead magnets, event files): private disk and how long a link works.
    'download_disk' => 'local',
    'download_link_hours' => (int) env('DOWNLOAD_LINK_HOURS', 72),

    // Written by the scheduler every minute; read by larawebkit:deploy-check.
    'heartbeat_file' => storage_path('framework/larawebkit-heartbeat'),

];

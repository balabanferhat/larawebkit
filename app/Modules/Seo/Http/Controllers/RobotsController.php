<?php

namespace App\Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\ModuleRegistry;
use App\Modules\Geo\AiCrawlerPolicy;
use App\Modules\Seo\CanonicalUrl;
use Illuminate\Http\Response;

/** robots.txt: default rules, AI crawler policy (GEO), editable extra rules and the sitemap. */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $admin = trim((string) config('larawebkit.admin_path', 'admin'), '/');
        $lines = [];

        if (! app()->environment('production') && config('larawebkit-seo.block_non_production', true)) {
            $lines = ['User-agent: *', 'Disallow: /', ''];
        } else {
            if (ModuleRegistry::enabled('geo')) {
                $lines[] = app(AiCrawlerPolicy::class)->robotsRules();
            }
            $lines[] = 'User-agent: *';
            $lines[] = "Disallow: /$admin";
            $lines[] = 'Disallow: /livewire/';
            $lines[] = 'Disallow: /_preview/';
            $lines[] = 'Disallow: /search';
            $lines[] = 'Allow: /';
            $lines[] = '';
        }

        if (filled($extra = setting('seo.robots_extra'))) {
            $lines[] = trim((string) $extra);
            $lines[] = '';
        }
        $lines[] = 'Sitemap: '.CanonicalUrl::for('/sitemap.xml');

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}

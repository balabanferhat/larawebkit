<?php

use App\Modules\Blocks\Types;

/*
|--------------------------------------------------------------------------
| Section blocks for pages
|--------------------------------------------------------------------------
|
| Block types offered in the page editor (in this order). A theme can limit
| them with "supports.blocks" in its theme.json and override any view with
| themes/{name}/views/blocks/{key}.blade.php. Recipe: docs/recipes/new-block.md
|
*/

return [

    'types' => [
        Types\Hero::class,
        Types\Features::class,
        Types\Cta::class,
        Types\Testimonials::class,
        Types\Faq::class,
        Types\Pricing::class,
        Types\Team::class,
        Types\Logos::class,
        Types\Stats::class,
        Types\Gallery::class,
        Types\Video::class,
        Types\RichText::class,
        Types\Newsletter::class,
        Types\Form::class,
        Types\LatestPosts::class,
        Types\Spacer::class,
    ],

    // Page presets offered when creating a page (App\Modules\Blocks\BlockPresets).
    'presets' => ['landing', 'about', 'services', 'contact'],

];

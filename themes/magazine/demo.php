<?php

use App\Modules\Themes\Demo\DemoBuilder;

/*
 * Demo for the Magazine theme: a fictional online magazine, "The Meridian".
 * Run with: php artisan larawebkit:demo magazine
 */
return function (DemoBuilder $demo) {
    $t = fn (string $key, array $r = []) => $demo->t($key, $r);

    $demo->settings([
        'general.site_name' => 'The Meridian',
        'general.tagline' => $t('tagline'),
        'general.contact_email' => 'editors@example.com',
        'organization.type' => 'Organization',
        'organization.name' => 'The Meridian',
        'organization.phone' => '',
        'organization.address' => '',
        'social.links' => ['X' => 'https://x.com/', 'Instagram' => 'https://www.instagram.com/', 'Facebook' => 'https://www.facebook.com/', 'YouTube' => 'https://www.youtube.com/'],
        'header.cta_label' => $t('subscribe'),
        'header.cta_url' => '/about#newsletter',
        'header.search' => true,
        'header.sticky' => false,
        'header.announcement' => '',
        'footer.text' => $t('footer'),
        'features.newsletter' => true,
    ]);

    foreach (['ada' => 'Ada Lindqvist', 'kerem' => 'Kerem Aydın', 'nora' => 'Nora Bennett'] as $key => $name) {
        $demo->author($key, $name, $t("authors.$key.role"), $t("authors.$key.bio"));
    }
    $sections = ['culture', 'technology', 'travel', 'food'];
    foreach ($sections as $key) {
        $demo->category($key, $t("sections.$key.name"), $t("sections.$key.text"));
    }

    $kinds = ['abstract', 'pattern', 'abstract', 'device'];
    // One colour story per section, so the front page does not look monochrome.
    $palettes = ['culture' => ['#c0262d', '#f4a261'], 'technology' => ['#1d3557', '#4cc9f0'], 'travel' => ['#2a9d8f', '#e9c46a'], 'food' => ['#bc6c25', '#606c38']];
    foreach (range(1, 14) as $i) {
        $section = $sections[($i - 1) % 4];
        $demo->post($t("posts.$i.title"), [
            'excerpt' => $t("posts.$i.excerpt"),
            'summary' => $i === 1 ? $t('posts.1.summary') : null,
            'categories' => [$section],
            'tags' => [$t("sections.$section.name")],
            'author' => ['ada', 'kerem', 'nora'][$i % 3],
            'image' => "story-$i",
            'image_kind' => $kinds[($i - 1) % 4],
            'palette' => $palettes[$section],
            'days_ago' => (int) round($i * 1.7),
        ]);
    }

    $demo->page('about', $t('pages.about'), [
        ['type' => 'hero', 'data' => ['variant' => 'centered', 'eyebrow' => $t('about.eyebrow'), 'heading' => $t('about.heading'), 'text' => $t('about.text')]],
        ['type' => 'rich_text', 'data' => ['variant' => 'narrow', 'content' => '<p>'.e($t('about.p1')).'</p><p>'.e($t('about.p2')).'</p><h2>'.e($t('about.values')).'</h2><ul><li>'.e($t('about.v1')).'</li><li>'.e($t('about.v2')).'</li><li>'.e($t('about.v3')).'</li></ul>']],
        ['type' => 'team', 'data' => ['variant' => 'grid', 'heading' => $t('about.team'), 'members' => collect(['ada' => 'Ada Lindqvist', 'kerem' => 'Kerem Aydın', 'nora' => 'Nora Bennett'])->map(fn ($name, $key) => ['name' => $name, 'role' => $t("authors.$key.role"), 'photo' => $demo->image("avatar-$key", 'portrait', 480, 480, $name)->id])->values()->all()]],
        ['type' => 'newsletter', 'data' => ['variant' => 'card', 'anchor' => 'newsletter', 'heading' => $t('subscribe_heading'), 'text' => $t('subscribe_text')]],
    ]);
    $demo->page('contact', $t('pages.contact'), [
        ['type' => 'form', 'data' => ['variant' => 'split', 'heading' => $t('contact.heading'), 'text' => $t('contact.text'), 'form' => 'contact']],
    ]);

    $sectionLinks = array_map(fn ($key) => [$t("sections.$key.name"), '/blog/category/'.$demo->category($key, '')->slug], $sections);
    $demo->menu('header', 'Sections', [
        [$t('pages.home'), '/'],
        ...$sectionLinks,
        [$t('pages.more'), null, [[$t('pages.about'), 'about'], [$t('pages.contact'), 'contact'], [$t('pages.archive'), '/blog']]],
    ]);
    $demo->menu('footer', $t('menus.sections'), $sectionLinks);
    $demo->menu('footer_secondary', $t('menus.magazine'), [[$t('pages.about'), 'about'], [$t('pages.contact'), 'contact'], [$t('pages.archive'), '/blog'], ['RSS', '/feed.xml']]);
    $demo->menu('legal', $t('menus.legal'), [[$t('menus.privacy'), '/privacy-policy'], [$t('menus.cookies'), '/cookie-policy']]);
};

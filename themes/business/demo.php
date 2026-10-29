<?php

use App\Modules\Themes\Demo\DemoBuilder;

/*
 * Demo for the Business theme: a fictional agency, "Brightline Studio".
 * Run with: php artisan larawebkit:demo business
 */
return function (DemoBuilder $demo) {
    $t = fn (string $key, array $r = []) => $demo->t($key, $r);

    $demo->settings([
        'general.site_name' => 'Brightline Studio',
        'general.tagline' => $t('tagline'),
        'general.contact_email' => 'hello@example.com',
        'organization.type' => 'Organization',
        'organization.name' => 'Brightline Studio',
        'organization.phone' => '+90 212 555 01 23',
        'organization.address' => $t('address'),
        'social.links' => ['LinkedIn' => 'https://www.linkedin.com/', 'Instagram' => 'https://www.instagram.com/', 'YouTube' => 'https://www.youtube.com/', 'GitHub' => 'https://github.com/'],
        'header.cta_label' => $t('cta'),
        'header.cta_url' => '/contact',
        'header.announcement' => $t('announcement'),
        'header.announcement_url' => '/services',
        'footer.text' => $t('footer'),
        'features.newsletter' => false,
    ]);

    $authors = [
        $demo->author('elif', 'Elif Yıldız', $t('people.elif.role'), $t('people.elif.bio')),
        $demo->author('daniel', 'Daniel Brooks', $t('people.daniel.role'), $t('people.daniel.bio')),
    ];
    $demo->category('cases', $t('categories.cases'), $t('categories.cases_text'));
    $demo->category('insights', $t('categories.insights'), $t('categories.insights_text'));

    $hero = $demo->image('hero-dashboard', 'device', 1600, 1200, $t('alt.hero'));
    $team = collect(['elif', 'daniel', 'mira', 'can'])->map(fn ($p) => ['name' => $t("people.$p.name"), 'role' => $t("people.$p.role"), 'bio' => $t("people.$p.bio"), 'photo' => $demo->image("team-$p", 'portrait', 800, 800, $t("people.$p.name"))->id])->all();
    $quotes = collect([1, 2, 3])->map(fn ($i) => ['quote' => $t("quotes.$i.quote"), 'name' => $t("quotes.$i.name"), 'role' => $t("quotes.$i.role"), 'rating' => 5, 'photo' => $demo->image("client-$i", 'portrait', 320, 320, $t("quotes.$i.name"))->id])->all();
    $services = collect(['strategy' => 'light-bulb', 'design' => 'paint-brush', 'development' => 'code-bracket', 'growth' => 'chart-bar', 'support' => 'shield-check', 'training' => 'academic-cap'])
        ->map(fn ($icon, $key) => ['icon' => $icon, 'title' => $t("services.$key.title"), 'text' => $t("services.$key.text"), 'url' => '/services', 'link_label' => $t('learn_more')])->values()->all();
    $logos = collect(['Northwind', 'Lumen', 'Orbital', 'Kestrel', 'Halcyon', 'Vertex'])->map(fn ($n) => ['name' => $n])->all();

    $demo->page('home', $t('pages.home'), [
        ['type' => 'hero', 'data' => ['variant' => 'split', 'eyebrow' => $t('hero.eyebrow'), 'heading' => $t('hero.heading'), 'text' => $t('hero.text'), 'image' => $hero->id, 'note' => $t('hero.note'),
            'buttons' => [['label' => $t('cta'), 'url' => '/contact', 'style' => 'primary'], ['label' => $t('hero.secondary'), 'url' => '#services', 'style' => 'secondary']]]],
        ['type' => 'logos', 'data' => ['variant' => 'row', 'heading' => $t('logos'), 'logos' => $logos, 'spacing' => 'compact']],
        ['type' => 'features', 'data' => ['variant' => 'cards', 'anchor' => 'services', 'background' => 'muted', 'eyebrow' => $t('services.eyebrow'), 'heading' => $t('services.heading'), 'intro' => $t('services.intro'), 'items' => $services]],
        ['type' => 'stats', 'data' => ['variant' => 'row', 'background' => 'brand', 'items' => collect([1, 2, 3, 4])->map(fn ($i) => ['value' => $t("stats.$i.value"), 'label' => $t("stats.$i.label")])->all()]],
        ['type' => 'features', 'data' => ['variant' => 'list', 'eyebrow' => $t('process.eyebrow'), 'heading' => $t('process.heading'), 'image' => $demo->image('process', 'abstract', 1200, 1000, $t('process.heading'))->id,
            'items' => collect([1, 2, 3])->map(fn ($i) => ['icon' => ['sparkles', 'puzzle-piece', 'rocket-launch'][$i - 1], 'title' => $t("process.$i.title"), 'text' => $t("process.$i.text")])->all()]],
        ['type' => 'testimonials', 'data' => ['variant' => 'grid', 'background' => 'muted', 'eyebrow' => $t('quotes.eyebrow'), 'heading' => $t('quotes.heading'), 'items' => $quotes]],
        ['type' => 'latest_posts', 'data' => ['variant' => 'cards', 'eyebrow' => $t('cases.eyebrow'), 'heading' => $t('cases.heading'), 'count' => 3, 'category_id' => $demo->category('cases', '')->id, 'link_label' => $t('cases.all')]],
        ['type' => 'faq', 'data' => ['variant' => 'accordion', 'heading' => $t('faq.heading'), 'items' => collect([1, 2, 3, 4])->map(fn ($i) => ['question' => $t("faq.$i.q"), 'answer' => $t("faq.$i.a")])->all()]],
        ['type' => 'cta', 'data' => ['variant' => 'boxed', 'heading' => $t('final.heading'), 'text' => $t('final.text'), 'buttons' => [['label' => $t('cta'), 'url' => '/contact', 'style' => 'primary'], ['label' => $t('final.secondary'), 'url' => 'tel:+902125550123', 'style' => 'secondary']]]],
    ], ['meta_description' => $t('tagline')]);

    $demo->page('services', $t('pages.services'), [
        ['type' => 'hero', 'data' => ['variant' => 'centered', 'eyebrow' => $t('services.eyebrow'), 'heading' => $t('services.page_heading'), 'text' => $t('services.intro'), 'buttons' => [['label' => $t('cta'), 'url' => '/contact', 'style' => 'primary']]]],
        ['type' => 'features', 'data' => ['variant' => 'grid', 'items' => $services]],
        ['type' => 'pricing', 'data' => ['variant' => 'cards', 'background' => 'muted', 'eyebrow' => $t('pricing.eyebrow'), 'heading' => $t('pricing.heading'), 'intro' => $t('pricing.intro'), 'note' => $t('pricing.note'),
            'plans' => collect(['start', 'grow', 'scale'])->map(fn ($p) => ['name' => $t("pricing.$p.name"), 'price' => $t("pricing.$p.price"), 'period' => $t("pricing.$p.period"), 'description' => $t("pricing.$p.description"), 'features' => $t("pricing.$p.features"), 'button_label' => $t('cta'), 'button_url' => '/contact', 'highlighted' => $p === 'grow', 'badge' => $p === 'grow' ? $t('pricing.popular') : ''])->all()]],
        ['type' => 'faq', 'data' => ['variant' => 'columns', 'heading' => $t('faq.heading'), 'items' => collect([1, 2, 3, 4])->map(fn ($i) => ['question' => $t("faq.$i.q"), 'answer' => $t("faq.$i.a")])->all()]],
        ['type' => 'cta', 'data' => ['variant' => 'banner', 'background' => 'brand', 'heading' => $t('final.heading'), 'buttons' => [['label' => $t('cta'), 'url' => '/contact', 'style' => 'primary']]]],
    ]);

    $demo->page('about', $t('pages.about'), [
        ['type' => 'hero', 'data' => ['variant' => 'background', 'eyebrow' => $t('about.eyebrow'), 'heading' => $t('about.heading'), 'text' => $t('about.text'), 'image' => $demo->image('office', 'abstract', 1920, 1100, $t('about.heading'))->id]],
        ['type' => 'rich_text', 'data' => ['variant' => 'narrow', 'content' => '<h2>'.e($t('about.story')).'</h2><p>'.e($t('about.story_1')).'</p><p>'.e($t('about.story_2')).'</p>']],
        ['type' => 'stats', 'data' => ['variant' => 'cards', 'items' => collect([1, 2, 3, 4])->map(fn ($i) => ['value' => $t("stats.$i.value"), 'label' => $t("stats.$i.label")])->all()]],
        ['type' => 'team', 'data' => ['variant' => 'cards', 'background' => 'muted', 'eyebrow' => $t('team.eyebrow'), 'heading' => $t('team.heading'), 'members' => $team]],
        ['type' => 'gallery', 'data' => ['variant' => 'grid', 'heading' => $t('gallery'), 'columns' => 3, 'images' => collect(range(1, 6))->map(fn ($i) => $demo->image("studio-$i", $i % 3 === 0 ? 'pattern' : 'abstract', 1200, 1200, $t('gallery').' '.$i)->id)->all()]],
        ['type' => 'cta', 'data' => ['variant' => 'boxed', 'heading' => $t('final.heading'), 'buttons' => [['label' => $t('cta'), 'url' => '/contact', 'style' => 'primary']]]],
    ]);

    $demo->page('contact', $t('pages.contact'), [
        ['type' => 'form', 'data' => ['variant' => 'split', 'eyebrow' => $t('contact.eyebrow'), 'heading' => $t('contact.heading'), 'text' => $t('contact.text'), 'form' => 'contact']],
        ['type' => 'faq', 'data' => ['variant' => 'accordion', 'background' => 'muted', 'heading' => $t('faq.heading'), 'items' => collect([1, 2])->map(fn ($i) => ['question' => $t("faq.$i.q"), 'answer' => $t("faq.$i.a")])->all()]],
    ]);

    foreach (range(1, 3) as $i) {
        $demo->post($t("cases.$i.title"), ['excerpt' => $t("cases.$i.excerpt"), 'categories' => ['cases'], 'tags' => [$t('tags.design'), $t('tags.web')], 'image' => "case-$i", 'image_kind' => $i === 2 ? 'device' : 'abstract', 'days_ago' => $i * 6]);
    }
    foreach (range(1, 3) as $i) {
        $demo->post($t("insights.$i.title"), ['excerpt' => $t("insights.$i.excerpt"), 'categories' => ['insights'], 'tags' => [$t('tags.strategy')], 'image' => "insight-$i", 'image_kind' => $i === 3 ? 'pattern' : 'abstract', 'days_ago' => $i * 4 + 1]);
    }

    $demo->menu('header', 'Main', [
        [$t('pages.services'), 'services', [[$t('services.strategy.title'), '/services#services'], [$t('services.design.title'), '/services'], [$t('services.development.title'), '/services'], [$t('services.growth.title'), '/services']]],
        [$t('pages.cases'), '/blog/category/'.$demo->category('cases', '')->slug],
        [$t('pages.about'), 'about'],
        [$t('pages.blog'), '/blog'],
        [$t('pages.contact'), 'contact'],
    ]);
    $demo->menu('footer', $t('menus.company'), [[$t('pages.about'), 'about'], [$t('pages.services'), 'services'], [$t('pages.cases'), '/blog/category/'.$demo->category('cases', '')->slug], [$t('pages.contact'), 'contact']]);
    $demo->menu('footer_secondary', $t('menus.resources'), [[$t('pages.blog'), '/blog'], [$t('categories.insights'), '/blog/category/'.$demo->category('insights', '')->slug], ['RSS', '/feed.xml']]);
    $demo->menu('legal', $t('menus.legal'), [[$t('menus.privacy'), '/privacy-policy'], [$t('menus.cookies'), '/cookie-policy']]);
};

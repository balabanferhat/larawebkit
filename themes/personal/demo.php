<?php

use App\Modules\Themes\Demo\DemoBuilder;

/*
 * Demo for the Personal brand theme: a fictional coach and author, "Maya Ersoy".
 * Run with: php artisan larawebkit:demo personal
 */
return function (DemoBuilder $demo) {
    $t = fn (string $key, array $r = []) => $demo->t($key, $r);

    $demo->settings([
        'general.site_name' => 'Maya Ersoy',
        'general.tagline' => $t('tagline'),
        'general.contact_email' => 'hello@example.com',
        'organization.type' => 'Person',
        'organization.name' => 'Maya Ersoy',
        'organization.phone' => '',
        'organization.address' => '',
        'social.links' => ['Instagram' => 'https://www.instagram.com/', 'YouTube' => 'https://www.youtube.com/', 'LinkedIn' => 'https://www.linkedin.com/'],
        'header.cta_label' => $t('cta'),
        'header.cta_url' => '/work-with-me',
        'header.search' => false,
        'header.announcement' => '',
        'footer.text' => $t('footer'),
        'features.newsletter' => true,
    ]);

    $maya = $demo->author('maya', 'Maya Ersoy', $t('role'), $t('bio'));
    $demo->category('work', $t('categories.work'), $t('categories.work_text'));
    $demo->category('life', $t('categories.life'), $t('categories.life_text'));
    $portrait = $demo->image('portrait', 'portrait', 1000, 1250, $t('alt.portrait'));
    $offers = collect(['session' => 'chat-bubble-left-right', 'book' => 'book-open', 'workshop' => 'calendar-days'])
        ->map(fn ($icon, $key) => ['icon' => $icon, 'title' => $t("offers.$key.title"), 'text' => $t("offers.$key.text"), 'url' => '/work-with-me', 'link_label' => $t("offers.$key.link")])->values()->all();

    $demo->page('home', $t('pages.home'), [
        ['type' => 'hero', 'data' => ['variant' => 'portrait', 'eyebrow' => $t('hero.eyebrow'), 'heading' => $t('hero.heading'), 'text' => $t('hero.text'), 'image' => $portrait->id,
            'buttons' => [['label' => $t('cta'), 'url' => '/work-with-me', 'style' => 'primary'], ['label' => $t('hero.secondary'), 'url' => '/about', 'style' => 'secondary']], 'note' => $t('hero.note')]],
        ['type' => 'logos', 'data' => ['variant' => 'row', 'heading' => $t('featured_in'), 'spacing' => 'compact', 'logos' => collect(['The Daily Example', 'Workplace Weekly', 'Podcast Nine', 'Founders FM'])->map(fn ($n) => ['name' => $n])->all()]],
        ['type' => 'newsletter', 'data' => ['variant' => 'card', 'heading' => $t('newsletter.heading'), 'text' => $t('newsletter.text'), 'button' => $t('newsletter.button')]],
        ['type' => 'features', 'data' => ['variant' => 'cards', 'eyebrow' => $t('offers.eyebrow'), 'heading' => $t('offers.heading'), 'items' => $offers]],
        ['type' => 'testimonials', 'data' => ['variant' => 'single', 'background' => 'muted', 'items' => [['quote' => $t('quotes.1.quote'), 'name' => $t('quotes.1.name'), 'role' => $t('quotes.1.role'), 'photo' => $demo->image('client-1', 'portrait', 320, 320, $t('quotes.1.name'))->id]]]],
        ['type' => 'latest_posts', 'data' => ['variant' => 'list', 'eyebrow' => $t('writing.eyebrow'), 'heading' => $t('writing.heading'), 'count' => 4, 'link_label' => $t('writing.all')]],
        ['type' => 'cta', 'data' => ['variant' => 'split', 'heading' => $t('final.heading'), 'text' => $t('final.text'), 'image' => $demo->image('workshop', 'abstract', 1200, 900, $t('final.heading'))->id, 'buttons' => [['label' => $t('cta'), 'url' => '/work-with-me', 'style' => 'primary']]]],
    ], ['meta_description' => $t('tagline')]);

    $demo->page('about', $t('pages.about'), [
        ['type' => 'hero', 'data' => ['variant' => 'split', 'eyebrow' => $t('about.eyebrow'), 'heading' => $t('about.heading'), 'text' => $t('about.text'), 'image' => $demo->image('portrait-2', 'portrait', 1000, 1000, $t('alt.portrait'))->id]],
        ['type' => 'rich_text', 'data' => ['variant' => 'narrow', 'content' => '<h2>'.e($t('about.story')).'</h2><p>'.e($t('about.story_1')).'</p><p>'.e($t('about.story_2')).'</p><blockquote><p>'.e($t('about.quote')).'</p></blockquote><p>'.e($t('about.story_3')).'</p>']],
        ['type' => 'stats', 'data' => ['variant' => 'row', 'background' => 'muted', 'items' => collect([1, 2, 3])->map(fn ($i) => ['value' => $t("stats.$i.value"), 'label' => $t("stats.$i.label")])->all()]],
        ['type' => 'gallery', 'data' => ['variant' => 'masonry', 'heading' => $t('about.gallery'), 'columns' => 3, 'images' => collect(range(1, 5))->map(fn ($i) => $demo->image("moment-$i", 'abstract', 1000, [1300, 900, 1000, 1200, 800][$i - 1], $t('about.gallery').' '.$i)->id)->all()]],
    ]);

    $demo->page('work-with-me', $t('pages.work'), [
        ['type' => 'hero', 'data' => ['variant' => 'centered', 'eyebrow' => $t('offers.eyebrow'), 'heading' => $t('work.heading'), 'text' => $t('work.text')]],
        ['type' => 'pricing', 'data' => ['variant' => 'cards', 'plans' => collect(['session', 'book', 'workshop'])->map(fn ($k) => ['name' => $t("offers.$k.title"), 'price' => $t("work.$k.price"), 'period' => $t("work.$k.period"), 'description' => $t("offers.$k.text"), 'features' => $t("work.$k.features"), 'button_label' => $t("offers.$k.link"), 'button_url' => '/contact', 'highlighted' => $k === 'session', 'badge' => $k === 'session' ? $t('work.popular') : ''])->all(), 'note' => $t('work.note')]],
        ['type' => 'video', 'data' => ['variant' => 'narrow', 'heading' => $t('work.video'), 'url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 'poster' => $demo->image('video-poster', 'abstract', 1600, 900, $t('work.video'))->id, 'caption' => $t('work.video_caption')]],
        ['type' => 'testimonials', 'data' => ['variant' => 'scroll', 'heading' => $t('quotes.heading'), 'items' => collect([1, 2, 3, 4])->map(fn ($i) => ['quote' => $t("quotes.$i.quote"), 'name' => $t("quotes.$i.name"), 'role' => $t("quotes.$i.role"), 'rating' => 5])->all()]],
        ['type' => 'faq', 'data' => ['variant' => 'accordion', 'heading' => $t('faq.heading'), 'items' => collect([1, 2, 3])->map(fn ($i) => ['question' => $t("faq.$i.q"), 'answer' => $t("faq.$i.a")])->all()]],
    ]);

    $demo->page('contact', $t('pages.contact'), [
        ['type' => 'form', 'data' => ['variant' => 'centered', 'eyebrow' => $t('pages.contact'), 'heading' => $t('contact.heading'), 'text' => $t('contact.text'), 'form' => 'contact']],
    ]);

    foreach (range(1, 6) as $i) {
        $demo->post($t("posts.$i.title"), ['excerpt' => $t("posts.$i.excerpt"), 'categories' => [$i % 2 ? 'work' : 'life'], 'tags' => [$t('tags.career')], 'author' => 'maya', 'image' => "post-$i", 'days_ago' => $i * 9 - 5]);
    }

    $demo->menu('header', 'Main', [
        [$t('pages.about'), 'about'],
        [$t('pages.work'), 'work-with-me'],
        [$t('pages.writing'), '/blog', [[$t('categories.work'), '/blog/category/'.$demo->category('work', '')->slug], [$t('categories.life'), '/blog/category/'.$demo->category('life', '')->slug]]],
        [$t('pages.contact'), 'contact'],
    ]);
    $demo->menu('footer', 'Footer', [[$t('pages.about'), 'about'], [$t('pages.work'), 'work-with-me'], [$t('pages.writing'), '/blog'], [$t('pages.contact'), 'contact']]);
    $demo->menu('legal', $t('menus.legal'), [[$t('menus.privacy'), '/privacy-policy'], [$t('menus.cookies'), '/cookie-policy']]);
};

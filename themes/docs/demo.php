<?php

use App\Modules\Themes\Demo\DemoBuilder;

/*
 * Demo for the Documentation theme: docs for a fictional open-source tool, "Orbit".
 * Run with: php artisan larawebkit:demo docs
 */
return function (DemoBuilder $demo) {
    $t = fn (string $key, array $r = []) => $demo->t($key, $r);

    $demo->settings([
        'general.site_name' => 'Orbit Docs',
        'general.tagline' => $t('tagline'),
        'general.contact_email' => 'docs@example.com',
        'organization.type' => 'Organization',
        'organization.name' => 'Orbit',
        'social.links' => ['GitHub' => 'https://github.com/', 'Mastodon' => 'https://mastodon.social/'],
        'header.cta_label' => 'GitHub',
        'header.cta_url' => 'https://github.com/',
        'header.search' => true,
        'header.announcement' => $t('announcement'),
        'header.announcement_url' => '/blog',
        'footer.text' => $t('footer'),
        'features.newsletter' => false,
    ]);
    $demo->author('team', 'Orbit Team', $t('team_role'), $t('team_bio'));

    $body = function (string $key, int $seed) use ($demo, $t) {
        return '<h2>'.e($t('steps')).'</h2><p>'.e($t('steps_text')).'</p>'
            .'<pre><code>'.e($t("pages.$key.code")).'</code></pre>'
            .'<p>'.e($t('tip')).' <code>orbit --help</code></p>'
            .$demo->article($seed);
    };

    $tree = [
        'getting-started' => ['installation', 'quick-start', 'configuration'],
        'guides' => ['projects', 'deploying', 'teams'],
        'reference' => ['cli', 'config-file', 'api'],
    ];
    $order = 0;
    foreach ($tree as $section => $children) {
        $parent = $demo->page($section, $t("pages.$section.title"), [], ['content' => $body($section, $order), 'summary' => $t("pages.$section.summary"), 'sort_order' => $order++]);
        foreach ($children as $i => $child) {
            $demo->page($child, $t("pages.$child.title"), [], ['content' => $body($child, $order), 'summary' => $t("pages.$child.summary"), 'parent_id' => $parent->id, 'sort_order' => $i]);
            $order++;
        }
    }
    $demo->page('faq', $t('pages.faq.title'), [
        ['type' => 'faq', 'data' => ['variant' => 'accordion', 'items' => collect([1, 2, 3, 4])->map(fn ($i) => ['question' => $t("faq.$i.q"), 'answer' => $t("faq.$i.a")])->all()]],
    ], ['summary' => $t('pages.faq.summary'), 'sort_order' => $order]);

    foreach ([1, 2, 3] as $i) {
        $demo->post($t("releases.$i.title"), ['excerpt' => $t("releases.$i.excerpt"), 'author' => 'team', 'image' => "release-$i", 'image_kind' => $i === 2 ? 'device' : 'pattern', 'days_ago' => $i * 21]);
    }

    $demo->menu('header', 'Docs', [
        [$t('pages.getting-started.title'), 'getting-started'],
        [$t('pages.guides.title'), 'guides', [[$t('pages.projects.title'), 'projects'], [$t('pages.deploying.title'), 'deploying'], [$t('pages.teams.title'), 'teams']]],
        [$t('pages.reference.title'), 'reference'],
        [$t('blog'), '/blog'],
    ]);
    $demo->menu('footer', $t('menus.docs'), [[$t('pages.getting-started.title'), 'getting-started'], [$t('pages.guides.title'), 'guides'], [$t('pages.reference.title'), 'reference'], [$t('pages.faq.title'), 'faq']]);
    $demo->menu('footer_secondary', $t('menus.community'), [['GitHub', 'https://github.com/'], [$t('blog'), '/blog'], ['RSS', '/feed.xml']]);
    $demo->menu('legal', $t('menus.legal'), [[$t('menus.privacy'), '/privacy-policy'], [$t('menus.cookies'), '/cookie-policy']]);
};

<?php

use App\Modules\Themes\Demo\DemoBuilder;

/*
 * Demo for the Minimal blog theme, and the fallback for child themes without a demo.php:
 * a simple personal blog with a few posts and an About page.
 * Run with: php artisan larawebkit:demo minimal
 */
return function (DemoBuilder $demo) {
    $t = fn (string $key) => $demo->t($key);

    $demo->settings([
        'general.site_name' => $t('blog.name'),
        'general.tagline' => $t('blog.tagline'),
        'header.cta_label' => '',
        'header.announcement' => '',
        'footer.text' => $t('blog.footer'),
    ]);
    $demo->author('writer', 'Sam Rivera', $t('blog.role'), $t('blog.bio'));
    $demo->category('notes', $t('blog.category'));

    foreach (array_values($demo->list('blog.posts')) as $i => $post) {
        $demo->post($post['title'], ['excerpt' => $post['excerpt'], 'categories' => ['notes'], 'author' => 'writer', 'image' => 'note-'.$i, 'image_kind' => ['abstract', 'pattern'][$i % 2], 'days_ago' => $i * 5 + 1]);
    }
    $about = $demo->page('about', $t('blog.about'), [], ['content' => '<p>'.e($t('blog.about_text')).'</p>'.$demo->article(2)]);

    $demo->menu('header', 'Main', [[$t('blog.blog'), '/blog'], [$t('blog.about'), 'about']]);
    $demo->menu('legal', 'Legal', [[$t('blog.privacy'), '/privacy-policy'], [$t('blog.cookies'), '/cookie-policy']]);
};

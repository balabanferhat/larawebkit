<?php

namespace App\Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Content\Models\Post;
use App\Modules\Media\Support\ContentImages;
use App\Modules\Seo\CanonicalUrl;
use App\Modules\Seo\SeoData;
use Illuminate\Http\Response;

/** RSS 2.0 feed of the latest published, indexable posts. */
class FeedController extends Controller
{
    public function __invoke(): Response
    {
        $posts = Post::indexable()->where('members_only', false)->where('locale', app()->getLocale())->with('author')
            ->latest('published_at')->limit((int) config('larawebkit-seo.feed_items', 20))->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/"><channel>'
            .'<title>'.e(SeoData::siteName()).'</title>'
            .'<link>'.e(CanonicalUrl::for(localized_path('/'))).'</link>'
            .'<description>'.e((string) setting('general.tagline')).'</description>'
            .'<language>'.e(app()->getLocale()).'</language>'
            .'<atom:link href="'.e(CanonicalUrl::for('/feed.xml')).'" rel="self" type="application/rss+xml"/>';

        foreach ($posts as $post) {
            $xml .= '<item>'
                .'<title>'.e($post->title).'</title>'
                .'<link>'.e(CanonicalUrl::for($post->publicPath())).'</link>'
                .'<guid isPermaLink="true">'.e(CanonicalUrl::for($post->publicPath())).'</guid>'
                .'<pubDate>'.$post->published_at->toRfc2822String().'</pubDate>'
                .($post->author ? '<author>'.e($post->author->email).' ('.e($post->author->name).')</author>' : '')
                .'<description>'.e($post->seoDescription()).'</description>'
                .'<content:encoded><![CDATA['.str_replace(']]>', ']]]]><![CDATA[>', ContentImages::render($post->content)).']]></content:encoded>'
                .'</item>';
        }

        return response($xml.'</channel></rss>', 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }
}

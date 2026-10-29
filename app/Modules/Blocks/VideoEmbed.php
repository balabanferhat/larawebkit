<?php

namespace App\Modules\Blocks;

/**
 * Privacy-friendly embeds: YouTube through youtube-nocookie.com, Vimeo with
 * dnt=1. Nothing loads from the video host until the visitor clicks play
 * (the poster is our own image, not the host's thumbnail).
 */
final class VideoEmbed
{
    /** @return array{provider: string, id: string, embed: string, url: string}|null */
    public static function parse(string $url): ?array
    {
        $url = trim($url);
        if (preg_match('~^(?:https?://)?(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/|youtube-nocookie\.com/embed/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return ['provider' => 'youtube', 'id' => $m[1], 'url' => "https://www.youtube.com/watch?v={$m[1]}",
                'embed' => "https://www.youtube-nocookie.com/embed/{$m[1]}?autoplay=1&rel=0&modestbranding=1"];
        }
        if (preg_match('~^(?:https?://)?(?:www\.|player\.)?vimeo\.com/(?:video/)?(\d{6,12})~', $url, $m)) {
            return ['provider' => 'vimeo', 'id' => $m[1], 'url' => "https://vimeo.com/{$m[1]}",
                'embed' => "https://player.vimeo.com/video/{$m[1]}?autoplay=1&dnt=1"];
        }

        return null;
    }
}

<?php

namespace App\Modules\Logs\Support;

/**
 * Classifies a user agent into human / bot / ai_bot without storing the raw string.
 */
class AgentClassifier
{
    public const HUMAN = 'human';

    public const BOT = 'bot';

    public const AI_BOT = 'ai_bot';

    /** @return array{class: string, bot: ?string} */
    public static function classify(?string $userAgent): array
    {
        $ua = (string) $userAgent;

        foreach (array_keys((array) config('larawebkit-geo.bots', [])) as $bot) {
            if (stripos($ua, $bot) !== false) {
                return ['class' => self::AI_BOT, 'bot' => $bot];
            }
        }

        if ($ua === '' || preg_match('/bot|crawl|spider|slurp|curl|wget|python|httpclient|headless|lighthouse|monitor|preview|facebookexternalhit|whatsapp/i', $ua)) {
            preg_match('/([A-Za-z0-9\-_.]*(?:bot|crawler|spider|slurp))/i', $ua, $m);

            return ['class' => self::BOT, 'bot' => isset($m[1]) ? mb_substr($m[1], 0, 60) : null];
        }

        return ['class' => self::HUMAN, 'bot' => null];
    }
}

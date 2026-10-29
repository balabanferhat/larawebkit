<?php

namespace App\Modules\Content\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonySanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Server-side sanitizing of editor output: only the elements and attributes
 * in config/larawebkit-editor.php (html.allowed_elements) survive; links get
 * safe schemes; scripts, styles, event handlers and iframes are dropped.
 */
class HtmlSanitizer
{
    private ?SymfonySanitizer $sanitizer = null;

    public function sanitize(?string $html): string
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return '';
        }

        $clean = $this->sanitizer()->sanitize($html);

        // The sanitizer does not parse CSS: keep only alignment and pixel sizes.
        return (string) preg_replace_callback('/\sstyle="([^"]*)"/i', function (array $m) {
            $kept = [];
            foreach (explode(';', html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5)) as $declaration) {
                $declaration = trim($declaration);
                if (preg_match('/^text-align:\s*(left|right|center|justify|start|end)$/i', $declaration)
                    || preg_match('/^(width|height):\s*\d+(\.\d+)?(px|%)$/i', $declaration)) {
                    $kept[] = $declaration;
                }
            }

            return $kept === [] ? '' : ' style="'.e(implode('; ', $kept)).'"';
        }, $clean);
    }

    private function sanitizer(): SymfonySanitizer
    {
        if ($this->sanitizer) {
            return $this->sanitizer;
        }

        $config = (new HtmlSanitizerConfig)
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowMediaSchemes(['http', 'https'])
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->withMaxInputLength(2_000_000);

        foreach ((array) config('larawebkit-editor.html.allowed_elements') as $element => $attributes) {
            $config = $config->allowElement($element, $attributes);
        }
        $config = $config->allowAttribute('style', ['p', 'h2', 'h3', 'h4', 'img']);

        return $this->sanitizer = new SymfonySanitizer($config);
    }
}

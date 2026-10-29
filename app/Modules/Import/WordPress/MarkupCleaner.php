<?php

namespace App\Modules\Import\WordPress;

use Closure;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Turns rendered WordPress (Gutenberg or classic) HTML into the editor's plain markup: no block
 * comments, classes, inline styles or wrappers; one h1 is the title, so headings start at h2;
 * embeds and iframes become links; images go through a callback that imports them.
 */
final class MarkupCleaner
{
    private const UNWRAP = ['div', 'span', 'section', 'article', 'header', 'footer', 'main', 'aside', 'center', 'font', 'small', 'big', 'mark', 'ins', 'del', 'abbr', 'cite', 'sup', 'sub', 'picture', 'label'];

    private const DROP = ['script', 'style', 'noscript', 'form', 'input', 'select', 'textarea', 'button', 'svg', 'source', 'link', 'meta', 'object', 'embed'];

    private const KEEP_ATTRIBUTES = ['a' => ['href', 'title'], 'img' => ['src', 'alt', 'data-id'], 'ol' => ['start'], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan']];

    /**
     * @param  (Closure(string $src, ?int $wpId, string $alt): ?array{src: string, id: int})|null  $image
     */
    public function clean(?string $html, string $siteUrl = '', ?Closure $image = null): string
    {
        $html = (string) preg_replace('/<!--.*?-->/s', '', (string) $html);
        if (trim($html) === '') {
            return '';
        }

        $doc = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"?><div id="lwk-import-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($doc);
        $root = $xpath->query('//*[@id="lwk-import-root"]')->item(0);
        if (! $root instanceof DOMElement) {
            return '';
        }

        foreach ($this->nodes($xpath, '//'.implode('|//', self::DROP), $root) as $node) {
            $node->parentNode?->removeChild($node);
        }

        // Embeds (YouTube, X, ...) and iframes: a plain link keeps the content without third-party scripts.
        foreach ($this->nodes($xpath, '//figure[contains(@class, "wp-block-embed")]', $root) as $figure) {
            $url = trim((string) $xpath->evaluate('string(.//*[contains(@class, "wp-block-embed__wrapper")])', $figure)) ?: (string) $xpath->evaluate('string(.//iframe/@src)', $figure);
            $this->replaceWithLink($doc, $figure, $url);
        }
        foreach ($this->nodes($xpath, '//iframe', $root) as $iframe) {
            $this->replaceWithLink($doc, $iframe, (string) $iframe->getAttribute('src'));
        }

        // Galleries and tables come wrapped in figures; image figures stay (figure + figcaption).
        foreach ($this->nodes($xpath, '//figure[contains(@class, "wp-block-gallery") or contains(@class, "wp-block-table") or contains(@class, "wp-block-pullquote")]', $root) as $figure) {
            $this->unwrap($figure);
        }

        foreach ($this->nodes($xpath, '//h1', $root) as $h) {
            $this->rename($doc, $h, 'h2');
        }
        foreach ($this->nodes($xpath, '//h5|//h6', $root) as $h) {
            $this->rename($doc, $h, 'h4');
        }

        foreach ($this->nodes($xpath, '//img', $root) as $img) {
            $this->image($img, $image);
        }

        foreach ($this->nodes($xpath, '//a', $root) as $a) {
            $href = trim((string) $a->getAttribute('href'));
            if ($siteUrl !== '' && $href !== '' && self::sameSite($href, $siteUrl)) {
                $parts = parse_url($href);
                $a->setAttribute('href', ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '').(isset($parts['fragment']) ? '#'.$parts['fragment'] : ''));
            }
            // Image links to the attachment file or page add nothing once the image is local.
            if ($a->getElementsByTagName('img')->length > 0 && trim($a->textContent) === '' && preg_match('#/wp-content/uploads/|attachment_id=|/attachment/#', $href)) {
                $this->unwrap($a);
            }
        }

        foreach ($this->nodes($xpath, '//'.implode('|//', self::UNWRAP), $root) as $node) {
            $this->unwrap($node);
        }

        foreach ($this->nodes($xpath, '//*', $root) as $element) {
            $keep = self::KEEP_ATTRIBUTES[$element->nodeName] ?? [];
            foreach (iterator_to_array($element->attributes) as $attribute) {
                if (! in_array($attribute->nodeName, $keep, true)) {
                    $element->removeAttribute($attribute->nodeName);
                }
            }
        }

        // Empty paragraphs and headings (spacers, &nbsp; lines).
        foreach ($this->nodes($xpath, '//p|//h2|//h3|//h4|//li|//figcaption', $root) as $node) {
            if (trim(str_replace("\u{00A0}", ' ', $node->textContent)) === '' && $node->getElementsByTagName('img')->length === 0 && $node->getElementsByTagName('br')->length === 0) {
                $node->parentNode?->removeChild($node);
            }
        }

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim((string) preg_replace("/\n{3,}/", "\n\n", $out));
    }

    public static function text(?string $html): string
    {
        // Block ends become spaces first, or "<p>Nerede?</p><p>Dışarıda</p>" reads "Nerede?Dışarıda".
        $html = (string) preg_replace('#(</(p|li|h[1-6]|blockquote|div|td|th|figcaption|pre)>|<br\s*/?>)#i', '$1 ', (string) $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string) preg_replace('/\s*(\[(…|\.\.\.)\]|Continue reading.*|Read more.*|Devamını oku.*)\s*$/iu', '', $text);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    public static function sameSite(string $url, string $siteUrl): bool
    {
        $host = fn (string $u) => preg_replace('/^www\./', '', strtolower((string) parse_url($u, PHP_URL_HOST)));

        return $host($url) !== '' && $host($url) === $host($siteUrl);
    }

    private function image(DOMElement $img, ?Closure $resolve): void
    {
        $src = trim((string) ($img->getAttribute('src') ?: $img->getAttribute('data-src')));
        if ($src === '' || str_starts_with($src, 'data:')) {
            $img->parentNode?->removeChild($img);

            return;
        }
        $wpId = preg_match('/\bwp-image-(\d+)\b/', (string) $img->getAttribute('class'), $m) ? (int) $m[1] : null;
        $alt = (string) $img->getAttribute('alt');
        $local = $resolve ? $resolve($src, $wpId, $alt) : null;
        $img->setAttribute('src', $local['src'] ?? $src);
        if ($local) {
            $img->setAttribute('data-id', (string) $local['id']);
        }
    }

    private function replaceWithLink(DOMDocument $doc, DOMNode $node, string $url): void
    {
        if (preg_match('#^https?://(?:www\.)?(?:youtube(?:-nocookie)?\.com/embed/|youtu\.be/)([\w-]{6,})#i', $url, $m)) {
            $url = 'https://www.youtube.com/watch?v='.$m[1];
        } elseif (preg_match('#^https?://player\.vimeo\.com/video/(\d+)#i', $url, $m)) {
            $url = 'https://vimeo.com/'.$m[1];
        }
        if (! preg_match('#^https?://#i', $url)) {
            $node->parentNode?->removeChild($node);

            return;
        }
        $p = $doc->createElement('p');
        $a = $doc->createElement('a');
        $a->setAttribute('href', $url);
        $a->appendChild($doc->createTextNode($url));
        $p->appendChild($a);
        $node->parentNode?->replaceChild($p, $node);
    }

    private function rename(DOMDocument $doc, DOMElement $node, string $name): void
    {
        $new = $doc->createElement($name);
        while ($node->firstChild) {
            $new->appendChild($node->firstChild);
        }
        $node->parentNode?->replaceChild($new, $node);
    }

    private function unwrap(DOMNode $node): void
    {
        $parent = $node->parentNode;
        if (! $parent) {
            return;
        }
        while ($node->firstChild) {
            $parent->insertBefore($node->firstChild, $node);
        }
        $parent->removeChild($node);
    }

    /** @return list<DOMElement> a static copy (the DOM changes while we walk it), deepest first */
    private function nodes(DOMXPath $xpath, string $query, DOMElement $root): array
    {
        $query = implode('|', array_map(fn ($q) => '.'.trim($q), explode('|', $query)));

        return array_reverse(array_values(array_filter(iterator_to_array($xpath->query($query, $root)), fn ($n) => $n instanceof DOMElement)));
    }
}

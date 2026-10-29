<?php

namespace App\Modules\Geo;

use App\Modules\Content\Models\Post;
use App\Modules\Seo\CanonicalUrl;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use Illuminate\Database\Eloquent\Model;

/**
 * Clean Markdown version of a page/post, served at {url}.md and for
 * "Accept: text/markdown", so AI agents can read the content without markup.
 */
class MarkdownTwin
{
    public function render(Model $content): string
    {
        $lines = ['# '.$content->title, ''];
        $meta = array_filter([
            'URL: '.CanonicalUrl::for($content->publicPath()),
            $content->author ? 'Author: '.$content->author->name : null,
            $content->published_at ? 'Published: '.$content->published_at->toDateString() : null,
            'Updated: '.$content->updated_at?->toDateString(),
        ]);
        $lines[] = implode("  \n", $meta);
        $lines[] = '';

        if (filled($content->summary)) {
            $lines[] = '> **'.__('larawebkit-front.tldr').':** '.trim(preg_replace('/\s+/', ' ', $content->summary));
            $lines[] = '';
        }

        if ($content instanceof Post && $content->members_only) {
            $lines[] = trim((string) $content->excerpt);
        } else {
            if (method_exists($content, 'blocksText') && ($blocks = $content->blocksText(withFaq: false)) !== '') {
                // Section blocks first (FAQ blocks are listed once, with the page FAQ, below).
                $lines[] = $blocks;
                $lines[] = '';
            }
            $lines[] = $this->htmlToMarkdown((string) $content->content);
        }

        $faq = method_exists($content, 'faqItems') ? $content->faqItems() : (array) $content->faq;
        if ($faq !== []) {
            $lines[] = '';
            $lines[] = '## '.__('larawebkit-front.faq');
            foreach ($faq as $item) {
                $lines[] = '';
                $lines[] = '### '.trim((string) ($item['question'] ?? ''));
                $lines[] = trim((string) ($item['answer'] ?? ''));
            }
        }

        if (filled($content->sources)) {
            $lines[] = '';
            $lines[] = '## '.__('larawebkit-front.sources');
            foreach (preg_split('/\R+/', trim((string) $content->sources)) as $source) {
                $lines[] = '- '.trim($source);
            }
        }

        return trim(implode("\n", $lines))."\n";
    }

    public function htmlToMarkdown(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }
        $doc = new DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="lwk-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        $root = $doc->getElementById('lwk-root') ?? $doc->documentElement;

        $md = $this->children($root);

        return trim(preg_replace("/\n{3,}/", "\n\n", $md));
    }

    private function children(DOMNode $node, int $depth = 0): string
    {
        $out = '';
        foreach ($node->childNodes as $child) {
            $out .= $this->node($child, $depth);
        }

        return $out;
    }

    private function node(DOMNode $node, int $depth): string
    {
        if ($node instanceof DOMText) {
            return preg_replace('/\s+/u', ' ', $node->wholeText);
        }
        if (! $node instanceof DOMElement) {
            return '';
        }
        $inner = fn () => trim($this->children($node, $depth));

        return match (strtolower($node->tagName)) {
            'h1', 'h2' => "\n\n## ".$inner()."\n\n",
            'h3' => "\n\n### ".$inner()."\n\n",
            'h4', 'h5', 'h6' => "\n\n#### ".$inner()."\n\n",
            'p', 'div', 'figure' => "\n\n".$inner()."\n\n",
            'br' => "  \n",
            'strong', 'b' => '**'.$inner().'**',
            'em', 'i' => '*'.$inner().'*',
            'code' => '`'.$inner().'`',
            'pre' => "\n\n```\n".trim($node->textContent)."\n```\n\n",
            'blockquote' => "\n\n> ".str_replace("\n", "\n> ", $inner())."\n\n",
            'a' => '['.$inner().']('.$this->absolute($node->getAttribute('href')).')',
            'img' => $node->getAttribute('alt') !== '' ? '!['.$node->getAttribute('alt').']('.$this->absolute($node->getAttribute('src')).')' : '',
            'ul', 'ol' => "\n\n".$this->list($node, $depth)."\n\n",
            'hr' => "\n\n---\n\n",
            'table' => "\n\n".$this->table($node)."\n\n",
            'script', 'style' => '',
            default => $this->children($node, $depth),
        };
    }

    private function list(DOMElement $list, int $depth): string
    {
        $ordered = strtolower($list->tagName) === 'ol';
        $i = 1;
        $lines = [];
        foreach ($list->childNodes as $li) {
            if ($li instanceof DOMElement && strtolower($li->tagName) === 'li') {
                $marker = $ordered ? ($i++).'.' : '-';
                $text = trim(preg_replace("/\n{2,}/", "\n", $this->children($li, $depth + 1)));
                $lines[] = str_repeat('  ', $depth).$marker.' '.str_replace("\n", "\n".str_repeat('  ', $depth + 1), $text);
            }
        }

        return implode("\n", $lines);
    }

    private function table(DOMElement $table): string
    {
        $rows = [];
        foreach ($table->getElementsByTagName('tr') as $tr) {
            $cells = [];
            foreach ($tr->childNodes as $cell) {
                if ($cell instanceof DOMElement && in_array(strtolower($cell->tagName), ['td', 'th'], true)) {
                    $cells[] = str_replace('|', '\|', trim(preg_replace('/\s+/', ' ', $cell->textContent)));
                }
            }
            $rows[] = '| '.implode(' | ', $cells).' |';
            if (count($rows) === 1) {
                $rows[] = '|'.str_repeat(' --- |', count($cells));
            }
        }

        return implode("\n", $rows);
    }

    private function absolute(string $url): string
    {
        return str_starts_with($url, '/') && ! str_starts_with($url, '//') ? CanonicalUrl::for($url) : $url;
    }
}

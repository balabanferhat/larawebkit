/*
 * larawebkit editor scoring (browser, live).
 *
 * This is a line-by-line mirror of App\Modules\Editor\Scoring\{TextAnalyzer,
 * SeoScorer, HumanScorer, GeoChecker}. The server score is authoritative; the
 * shared fixtures in tests/fixtures/scoring are run against both versions
 * (Pest + `node --test tests/js/*.test.mjs`) so they never drift apart.
 *
 * Plain script, no build step: it exposes globalThis.LarawebkitScoring.
 */
(function (root) {
    const WORD = /[\p{L}\p{N}]+(?:['’\-][\p{L}\p{N}]+)*/gu;

    const ENTITIES = {
        amp: '&', lt: '<', gt: '>', quot: '"', apos: "'", nbsp: ' ', hellip: '…', mdash: '—', ndash: '–',
        lsquo: '‘', rsquo: '’', ldquo: '“', rdquo: '”', laquo: '«', raquo: '»', copy: '©', reg: '®', trade: '™',
    };

    function decodeEntities(s) {
        return s.replace(/&(#x[0-9a-f]+|#[0-9]+|[a-z]+);/gi, (m, e) => {
            if (e[0] === '#') {
                const code = e[1] === 'x' || e[1] === 'X' ? parseInt(e.slice(2), 16) : parseInt(e.slice(1), 10);
                return Number.isFinite(code) ? String.fromCodePoint(code) : m;
            }
            return Object.prototype.hasOwnProperty.call(ENTITIES, e) ? ENTITIES[e] : m;
        });
    }

    function htmlToText(html) {
        let t = String(html || '').replace(/<br\s*\/?>/gi, '\n');
        t = t.replace(/<\/(p|h[1-6]|li|blockquote|div|figcaption|tr|pre)>/gi, '\n');
        t = t.replace(/<[^>]*>/g, ' ');
        t = decodeEntities(t).replace(/ /g, ' ');
        t = t.replace(/[ \t]+/gu, ' ').replace(/ *\n */gu, '\n');
        return t.trim();
    }

    function words(text) {
        return (String(text || '').match(WORD) || []).map((w) => w.replace(/’/g, "'").toLowerCase());
    }

    function sentences(text) {
        return String(text || '')
            .split(/(?<=[.!?…])\s+|\n+/u)
            .map((s) => s.trim())
            .filter((s) => s !== '' && words(s).length > 0);
    }

    function normalize(text) {
        return ' ' + words(text).join(' ') + ' ';
    }

    function countOccurrences(haystack, needle) {
        let count = 0;
        let pos = 0;
        while ((pos = haystack.indexOf(needle, pos)) !== -1) {
            count++;
            pos += needle.length;
        }
        return count;
    }

    function analyze(html) {
        html = String(html || '');
        const text = htmlToText(html);
        const w = words(text);
        const norm = ' ' + w.join(' ') + ' ';
        return {
            html,
            text,
            words: w,
            sentences: sentences(text),
            countPhrase(phrase) {
                const n = normalize(phrase);
                return n.trim() === '' ? 0 : countOccurrences(norm, n);
            },
            countTag(tag) {
                return (html.match(new RegExp('<' + tag + '(\\s[^>]*)?>', 'gi')) || []).length;
            },
            headings(tag) {
                const re = new RegExp('<' + tag + '(?:\\s[^>]*)?>([\\s\\S]*?)<\\/' + tag + '>', 'gi');
                return [...html.matchAll(re)].map((m) => htmlToText(m[1]));
            },
            firstParagraph() {
                const m = html.match(/<p(?:\s[^>]*)?>([\s\S]*?)<\/p>/i);
                return m ? htmlToText(m[1]) : (this.sentences[0] || '');
            },
            images() {
                return (html.match(/<img\s[^>]*>/gi) || []).map((tag) => {
                    const a = tag.match(/\salt\s*=\s*"([^"]*)"/i);
                    return { alt: decodeEntities(a ? a[1] : '').trim() };
                });
            },
            links() {
                return [...html.matchAll(/<a\s[^>]*href\s*=\s*"([^"]*)"/gi)].map((m) => decodeEntities(m[1]));
            },
        };
    }

    function makeTranslator(lang) {
        return function t(key, params) {
            let s = key.split('.').reduce((o, k) => (o && typeof o === 'object' ? o[k] : undefined), lang || {});
            if (typeof s !== 'string') {
                return '';
            }
            Object.keys(params || {})
                .sort((a, b) => b.length - a.length)
                .forEach((k) => {
                    s = s.split(':' + k).join(String(params[k]));
                });
            return s;
        };
    }

    function check(key, passed, points, max, hint) {
        return { key, passed, points, max, hint };
    }

    function total(checks) {
        const max = checks.reduce((s, c) => s + c.max, 0);
        const earned = checks.reduce((s, c) => s + c.points, 0);
        return max > 0 ? Math.round((earned * 100) / max) : 0;
    }

    function densityOf(input, a) {
        const kw = (input.keywords || [])[0] || '';
        const kwWords = words(kw).length;
        if (kw === '' || a.words.length === 0 || kwWords === 0) {
            return 0;
        }
        return (a.countPhrase(kw) * kwWords * 100) / a.words.length;
    }

    function stripWww(h) {
        return String(h).replace(/^www\./i, '').toLowerCase();
    }

    function seoScore(input, config, lang) {
        const t = makeTranslator(lang);
        const a = analyze(input.html);
        const n = a.words.length;
        const out = [];
        const binary = (key, cfg, passed) => check(key, passed, passed ? cfg.points : 0, cfg.points, t('seo.' + key + '.' + (passed ? 'pass' : 'fail')));
        const tiered = (key, cfg, value, params) => {
            const tiers = Object.keys(cfg.tiers).map(Number).sort((x, y) => y - x);
            const max = cfg.max !== undefined ? cfg.max : Math.max(...tiers.map((k) => cfg.tiers[k]));
            const p = Object.assign({}, params, { target: tiers[0] });
            for (const threshold of tiers) {
                if (value >= threshold) {
                    const pts = cfg.tiers[threshold];
                    return check(key, pts >= max, pts, max, t('seo.' + key + '.' + (pts >= max ? 'pass' : 'partial'), p));
                }
            }
            return check(key, false, cfg.fallback || 0, max, t('seo.' + key + '.fail', p));
        };

        for (const [key, cfg] of Object.entries(config.checks || {})) {
            if (cfg.enabled === false) continue;
            if (key === 'word_count') {
                out.push(tiered(key, cfg, n, { count: n }));
            } else if (key === 'keyword_in_title') {
                const kws = input.keywords || [];
                if (kws.length === 0) {
                    out.push(check(key, false, 0, cfg.points, t('seo.no_keywords')));
                    continue;
                }
                const title = normalize(input.title);
                const found = kws.some((k) => {
                    const needle = normalize(k);
                    return needle.trim() !== '' && title.includes(needle);
                });
                out.push(binary(key, cfg, found));
            } else if (key === 'meta_description') {
                const len = [...String(input.metaDescription || '').trim()].length;
                const p = { length: len, min: cfg.min, max: cfg.max };
                if (len === 0) out.push(check(key, false, 0, cfg.points, t('seo.meta_description.fail', p)));
                else if (len >= cfg.min && len <= cfg.max) out.push(check(key, true, cfg.points, cfg.points, t('seo.meta_description.pass', p)));
                else out.push(check(key, false, cfg.partial, cfg.points, t('seo.meta_description.partial', p)));
            } else if (key === 'h2_count') {
                const h2 = a.countTag('h2');
                out.push(tiered(key, cfg, h2, { count: h2 }));
            } else if (key === 'keyword_density') {
                const d = densityOf(input, a);
                const p = { density: d.toFixed(1), min: cfg.min, max: cfg.max };
                if (d <= 0) out.push(check(key, false, 0, cfg.points, t('seo.keyword_density.fail', p)));
                else if (d >= cfg.min && d <= cfg.max) out.push(check(key, true, cfg.points, cfg.points, t('seo.keyword_density.pass', p)));
                else out.push(check(key, false, cfg.partial, cfg.points, t('seo.keyword_density.partial', p)));
            } else if (key === 'has_list') {
                out.push(binary(key, cfg, a.countTag('ul') + a.countTag('ol') > 0));
            } else if (key === 'has_bold') {
                out.push(binary(key, cfg, a.countTag('strong') + a.countTag('b') > 0));
            } else if (key === 'image_alt') {
                out.push(binary(key, cfg, !!input.featuredImageHasAlt || a.images().some((i) => i.alt !== '')));
            } else if (key === 'internal_link') {
                const host = input.siteHost || '';
                const internal = a.links().some((href) => {
                    if (href.startsWith('/') && !href.startsWith('//')) return true;
                    const m = href.match(/^(?:[a-z][a-z0-9+.\-]*:)?\/\/(?:[^\/?#@]*@)?([^\/?#:]+)/i);
                    return host !== '' && !!m && stripWww(m[1]) === stripWww(host);
                });
                out.push(binary(key, cfg, internal));
            }
        }

        return { score: total(out), checks: out, meta: { words: n, density: densityOf(input, a) } };
    }

    function humanScore(input, config, cliches, lang) {
        const t = makeTranslator(lang);
        const c = config;
        const a = analyze(input.html);
        let score = c.start !== undefined ? c.start : 100;
        const checks = [];

        const lengths = a.sentences.map((s) => words(s).length);
        const n = lengths.length;
        const mean = n > 0 ? lengths.reduce((s, l) => s + l, 0) / n : 0;
        let cv = null;
        if (n >= (c.min_sentences_for_variation || 3) && mean > 0) {
            const variance = lengths.reduce((s, l) => s + (l - mean) ** 2, 0) / n;
            cv = Math.sqrt(variance) / mean;
            let penalty = 0;
            for (const rule of c.variation) {
                if (cv < rule.below) {
                    penalty = rule.penalty;
                    break;
                }
            }
            score -= penalty;
            checks.push(check('variation', penalty === 0, penalty ? -penalty : 0, 0, t('human.variation.' + (penalty === 0 ? 'pass' : 'fail'), { cv: cv.toFixed(2) })));
        }

        const long = mean > c.long_sentences.average_above;
        if (long) score -= c.long_sentences.penalty;
        checks.push(check('long_sentences', !long, long ? -c.long_sentences.penalty : 0, 0, t('human.long_sentences.' + (long ? 'fail' : 'pass'), { average: mean.toFixed(1) })));

        const found = (cliches || []).filter((p) => a.countPhrase(p) > 0);
        const clichePenalty = Math.min(found.length * c.cliches.penalty, c.cliches.max_penalty);
        score -= clichePenalty;
        checks.push(check('cliches', found.length === 0, clichePenalty ? -clichePenalty : 0, 0, found.length === 0 ? t('human.cliches.pass') : t('human.cliches.fail', { list: found.join(', ') })));

        let runs = 0;
        let runLength = 0;
        let prev = null;
        const minRun = c.repeated_starts.min_run;
        for (const s of a.sentences) {
            const first = words(s)[0] || '';
            if (first === prev) {
                runLength++;
            } else {
                if (runLength >= minRun) runs++;
                runLength = 1;
                prev = first;
            }
        }
        if (runLength >= minRun) runs++;
        const repeatPenalty = Math.min(runs * c.repeated_starts.penalty, c.repeated_starts.max_penalty);
        score -= repeatPenalty;
        checks.push(check('repeated_starts', runs === 0, repeatPenalty ? -repeatPenalty : 0, 0, t('human.repeated_starts.' + (runs === 0 ? 'pass' : 'fail'), { count: runs })));

        const hasQuestion = a.text.includes('?');
        if (hasQuestion) score += c.question_bonus;
        checks.push(check('question', hasQuestion, hasQuestion ? c.question_bonus : 0, c.question_bonus, t('human.question.' + (hasQuestion ? 'pass' : 'fail'))));

        const readerWords = ((c.reader_words || {})[input.locale] || []).map((w) => w.toLowerCase());
        const addresses = readerWords.some((w) => a.words.includes(w));
        if (addresses) score += c.reader_bonus;
        checks.push(check('reader', addresses, addresses ? c.reader_bonus : 0, c.reader_bonus, t('human.reader.' + (addresses ? 'pass' : 'fail'))));

        return {
            score: Math.max(0, Math.min(100, score)),
            checks,
            meta: { sentences: n, average_sentence: Math.round(mean * 100) / 100, cv: cv === null ? null : Math.round(cv * 10000) / 10000, cliches: found },
        };
    }

    function geoScore(input, config, lang) {
        const t = makeTranslator(lang);
        const a = analyze(input.html);
        const out = [];
        for (const [key, cfg] of Object.entries(config.checks || {})) {
            if (cfg.enabled === false) continue;
            let passed = null;
            if (key === 'has_summary') passed = String(input.summary || '').trim() !== '';
            else if (key === 'question_h2') passed = a.headings('h2').some((h) => h.trimEnd().endsWith('?'));
            else if (key === 'faq_or_lists') passed = (input.faq || []).length > 0 || a.countTag('ul') + a.countTag('ol') + a.countTag('table') > 0;
            else if (key === 'has_sources') passed = String(input.sources || '').replace(/<[^>]*>/g, '').trim() !== '';
            else if (key === 'has_author') passed = !!input.hasAuthor;
            else if (key === 'answer_first') {
                const w = words(a.firstParagraph()).length;
                passed = w >= cfg.min_words && w <= cfg.max_words;
            }
            if (passed === null) continue;
            out.push(check(key, passed, passed ? cfg.points : 0, cfg.points, t('geo.' + key + '.' + (passed ? 'pass' : 'fail'), { min: cfg.min_words || '', max: cfg.max_words || '' })));
        }
        return { score: total(out), checks: out };
    }

    root.LarawebkitScoring = { htmlToText, words, sentences, analyze, seoScore, humanScore, geoScore };
})(typeof globalThis !== 'undefined' ? globalThis : window);

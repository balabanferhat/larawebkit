/*
 * larawebkit content editor (admin, no build step).
 *
 * Alpine components used by resources/views/editor/*.blade.php:
 *   lwkEditorPanel  live SEO / human / GEO scores, publish gate, autosave, AI assistant
 *   lwkSnippet      Google-style snippet preview + meta description counter
 *   lwkFeatured     featured image preview in the sidebar
 *   lwkMediaModal   WordPress-style media modal (library, upload, Pexels, details)
 *
 * They are plain globals so the x-data expressions work regardless of load order.
 */
(function () {
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    async function api(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.body && !(options.body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}),
                ...(options.headers || {}),
            },
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(data.message || response.statusText);
            error.status = response.status;
            error.data = data;
            throw error;
        }
        return data;
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    }

    /** The TipTap editor of the (first) Filament RichEditor on the page. */
    function findEditor() {
        const el = document.querySelector('[data-rich-editor-key]');
        if (!el || !window.Alpine) return null;
        try {
            const data = window.Alpine.$data(el);
            return data && typeof data.getEditor === 'function' ? data.getEditor() : null;
        } catch (e) {
            return null;
        }
    }

    function editorHtml() {
        const editor = findEditor();
        if (editor) return editor.getHTML();
        const pm = document.querySelector('.fi-fo-rich-editor .ProseMirror, .ProseMirror');
        return pm ? pm.innerHTML : '';
    }

    function wireGet($wire, path, fallback = '') {
        try {
            const v = $wire.$get(path);
            return v === undefined || v === null ? fallback : v;
        } catch (e) {
            return fallback;
        }
    }

    function credit(lang, photographer, photographerUrl, sourceUrl) {
        const p = '<a href="' + escapeHtml(photographerUrl || sourceUrl) + '" target="_blank" rel="noopener">' + escapeHtml(photographer) + '</a>';
        const s = '<a href="' + escapeHtml(sourceUrl || 'https://www.pexels.com') + '" target="_blank" rel="noopener">Pexels</a>';
        return String(lang.attribution || 'Photo by :photographer on :source').replace(':photographer', p).replace(':source', s);
    }

    window.lwkEditorPanel = function (config) {
        return {
            config,
            open: 'seo',
            panels: [],
            words: 0,
            readingTime: 0,
            gate: config.scoring.gate,
            gateFailures: [],
            featuredHasAlt: false,
            lastSnapshot: null,
            dirty: false,
            autosaveText: '',
            brief: '',
            busy: false,
            aiStatus: '',
            aiOutput: '',

            init() {
                this.recompute();
                this.lastSnapshot = this.snapshot();
                setInterval(() => this.recompute(), 1500);
                window.addEventListener('lwk-featured-changed', (e) => {
                    this.featuredHasAlt = !!(e.detail && e.detail.alt);
                    this.recompute();
                });
                if (config.canAutosave && config.autosaveSeconds > 0) {
                    setInterval(() => this.autosave(), config.autosaveSeconds * 1000);
                }
            },

            input() {
                const $wire = this.$wire;
                const keywords = wireGet($wire, 'data.keywords', []);
                const faq = wireGet($wire, 'data.faq', {});
                return {
                    title: wireGet($wire, 'data.title'),
                    html: editorHtml(),
                    metaDescription: wireGet($wire, 'data.meta_description'),
                    keywords: Array.isArray(keywords) ? keywords : Object.values(keywords || {}),
                    locale: wireGet($wire, 'data.locale') || document.documentElement.lang || 'en',
                    featuredImageHasAlt: this.featuredHasAlt,
                    summary: wireGet($wire, 'data.summary'),
                    faq: Object.values(faq || {}).filter((i) => i && String(i.question || '').trim() !== ''),
                    sources: wireGet($wire, 'data.sources'),
                    hasAuthor: !!wireGet($wire, 'data.author_id', null),
                    siteHost: config.scoring.siteHost,
                };
            },

            snapshot() {
                const i = this.input();
                return JSON.stringify([i.title, i.html, i.metaDescription, i.keywords, i.summary, i.faq, i.sources]);
            },

            recompute() {
                const S = window.LarawebkitScoring;
                if (!S || !this.$wire) return;
                const input = this.input();
                const lang = config.scoring.lang;
                const seo = S.seoScore(input, config.scoring.seo, lang);
                const human = S.humanScore(input, config.scoring.human, config.scoring.cliches[input.locale] || [], lang);
                const geo = S.geoScore(input, config.scoring.geo, lang);
                this.panels = [
                    { key: 'seo', label: 'SEO', result: seo },
                    { key: 'human', label: config.editorLang.human, result: human },
                    { key: 'geo', label: 'GEO', result: geo },
                ];
                this.words = seo.meta.words;
                this.readingTime = Math.max(1, Math.round(this.words / 220));

                const g = this.gate;
                const f = [];
                const t = (k, p) => (lang.gate && lang.gate[k] ? lang.gate[k].replace(/:(\w+)/g, (m, x) => (x in p ? p[x] : m)) : k);
                if (g.enabled) {
                    if (seo.score < g.min_seo) f.push(t('seo', { score: seo.score, min: g.min_seo }));
                    if (this.words < g.min_words) f.push(t('words', { count: this.words, min: g.min_words }));
                    if (human.score < g.min_human) f.push(t('human', { score: human.score, min: g.min_human }));
                }
                this.gateFailures = f;

                const snap = this.snapshot();
                if (this.lastSnapshot !== null && snap !== this.lastSnapshot) this.dirty = true;
            },

            async autosave() {
                if (!this.dirty || !this.$wire) return;
                try {
                    const time = await this.$wire.autosave();
                    if (time) {
                        this.autosaveText = (config.editorLang.autosaved || 'Draft autosaved at :time').replace(':time', time);
                        this.lastSnapshot = this.snapshot();
                        this.dirty = false;
                    }
                } catch (e) {
                    this.autosaveText = config.editorLang.autosave_failed || 'Autosave failed';
                }
            },

            async ai(action, payload) {
                this.busy = true;
                this.aiStatus = config.lang.working;
                this.aiOutput = '';
                try {
                    let result = await api(config.ai.run + '/' + action, { method: 'POST', body: JSON.stringify({ locale: this.input().locale, ...payload }) });
                    if (result.task) {
                        this.aiStatus = config.lang.queued;
                        while (result.status === 'pending' || result.status === 'running') {
                            await new Promise((r) => setTimeout(r, 2500));
                            result = await api(config.ai.run + '/tasks/' + result.task);
                        }
                        if (result.status === 'failed') throw new Error(result.error || 'AI task failed');
                    }
                    this.aiStatus = '';
                    return result.output || '';
                } catch (e) {
                    this.aiStatus = e.message;
                    return null;
                } finally {
                    this.busy = false;
                }
            },

            async aiDraft() {
                const i = this.input();
                const html = await this.ai('draft', { title: i.title, brief: this.brief, keywords: i.keywords });
                if (!html) return;
                const editor = findEditor();
                if (editor && (editor.isEmpty || window.confirm(config.lang.replace_confirm))) {
                    editor.commands.setContent(html.replace(/^```(?:html)?\s*|\s*```$/g, ''), true);
                    this.aiStatus = config.lang.applied;
                } else {
                    this.aiOutput = html;
                }
            },

            async aiRewrite() {
                const editor = findEditor();
                if (!editor || editor.state.selection.empty) {
                    this.aiStatus = config.lang.rewrite_hint;
                    return;
                }
                const { from, to } = editor.state.selection;
                const text = editor.state.doc.textBetween(from, to, '\n');
                const out = await this.ai('rewrite', { text });
                if (out) {
                    editor.chain().focus().insertContentAt({ from, to }, out.replace(/^```(?:html)?\s*|\s*```$/g, '')).run();
                    this.aiStatus = config.lang.applied;
                }
            },

            async aiMeta() {
                const i = this.input();
                const out = await this.ai('meta_description', { title: i.title, keywords: i.keywords, text: i.html });
                if (out) {
                    this.$wire.$set('data.meta_description', out.trim().replace(/^"|"$/g, ''), false);
                    this.aiStatus = config.lang.applied;
                }
            },

            async aiTags() {
                const i = this.input();
                const out = await this.ai('tags', { title: i.title, text: i.html });
                if (out) this.aiOutput = out;
            },

            async aiTopics() {
                const out = await this.ai('topics', {});
                if (out) this.aiOutput = out;
            },

            level(score) {
                return score >= 70 ? 'good' : score >= 45 ? 'ok' : 'bad';
            },
        };
    };

    window.lwkSnippet = function (config) {
        return {
            ...config,
            title: '',
            description: '',
            url: '',
            count: 0,
            countClass: '',
            init() {
                this.update();
                setInterval(() => this.update(), 800);
            },
            update() {
                if (!this.$wire) return;
                const metaTitle = wireGet(this.$wire, 'data.meta_title');
                const title = metaTitle || wireGet(this.$wire, 'data.title');
                const desc = String(wireGet(this.$wire, 'data.meta_description') || '');
                const slug = wireGet(this.$wire, 'data.slug');
                this.title = (title ? title + ' ' + this.separator + ' ' : '') + this.site;
                this.count = [...desc.trim()].length;
                const fallback = window.LarawebkitScoring ? window.LarawebkitScoring.htmlToText(editorHtml()).slice(0, 160) : '';
                this.description = desc || fallback;
                this.url = (this.prefix + (slug || '')).replace(/^https?:\/\//, '').replace(/\/$/, '').replace(/\//g, ' › ');
                this.countClass = this.count === 0 ? 'lwk-error' : this.count >= this.min && this.count <= this.max ? 'lwk-success' : 'lwk-warning';
            },
        };
    };

    window.lwkFeatured = function (config) {
        return {
            item: null,
            init() {
                this.refresh();
                window.addEventListener('lwk-featured-selected', (e) => {
                    this.item = e.detail;
                    this.$wire.$set('data.featured_media_id', e.detail.id, false);
                    window.dispatchEvent(new CustomEvent('lwk-featured-changed', { detail: e.detail }));
                });
            },
            async refresh() {
                const id = wireGet(this.$wire, 'data.featured_media_id', null);
                if (!id) return;
                try {
                    this.item = (await api(config.api + '/' + id)).data;
                    window.dispatchEvent(new CustomEvent('lwk-featured-changed', { detail: this.item }));
                } catch (e) {
                    this.item = null;
                }
            },
            remove() {
                this.item = null;
                this.$wire.$set('data.featured_media_id', null, false);
                window.dispatchEvent(new CustomEvent('lwk-featured-changed', { detail: null }));
            },
            credit() {
                return this.item ? credit({ attribution: config.attribution }, this.item.photographer, this.item.photographer_url, this.item.source_url) : '';
            },
        };
    };

    window.lwkMediaModal = function (config) {
        return {
            config,
            open: false,
            mode: 'insert',
            editor: null,
            tab: 'library',
            items: [],
            page: 1,
            hasMore: false,
            loading: false,
            search: '',
            selected: null,
            insertSize: 'large',
            insertAlign: 'center',
            dragging: false,
            uploads: [],
            busy: false,
            copied: false,
            savedFlash: false,
            pexels: { q: '', orientation: '', page: 1, photos: [], hasMore: false, selected: null, error: '', started: false },

            show(detail) {
                this.mode = (detail && detail.mode) || 'insert';
                this.editor = (detail && detail.editor) || findEditor();
                this.open = true;
                this.selected = null;
                this.pexels.selected = null;
                if (this.items.length === 0) this.load(true);
                document.body.style.overflow = 'hidden';
            },

            close() {
                if (!this.open) return;
                this.open = false;
                document.body.style.overflow = '';
            },

            async load(reset) {
                if (reset) {
                    this.page = 1;
                    this.items = [];
                }
                this.loading = true;
                try {
                    const url = new URL(config.api, window.location.origin);
                    url.searchParams.set('page', this.page);
                    if (this.search) url.searchParams.set('search', this.search);
                    const res = await api(url.toString());
                    this.items = this.items.concat(res.data);
                    this.hasMore = res.has_more;
                    this.page = res.next_page || this.page;
                } finally {
                    this.loading = false;
                }
            },

            async select(item) {
                this.pexels.selected = null;
                this.selected = { ...item };
                try {
                    this.selected = (await api(config.api + '/' + item.id)).data;
                } catch (e) {
                    /* keep the basic item */
                }
            },

            async saveMeta() {
                if (!this.selected) return;
                const res = await api(config.api + '/' + this.selected.id, {
                    method: 'PATCH',
                    body: JSON.stringify({ title: this.selected.title, alt: this.selected.alt, caption: this.selected.caption }),
                });
                this.selected = res.data;
                const idx = this.items.findIndex((i) => i.id === res.data.id);
                if (idx >= 0) this.items[idx] = { ...this.items[idx], alt: res.data.alt, title: res.data.title };
                this.savedFlash = true;
                setTimeout(() => (this.savedFlash = false), 1500);
            },

            async copyUrl() {
                if (!this.selected?.url) return;
                try {
                    await navigator.clipboard.writeText(this.selected.url);
                } catch (e) {
                    window.prompt('URL', this.selected.url);
                }
                this.copied = true;
                setTimeout(() => (this.copied = false), 1500);
            },

            async destroy() {
                if (!this.selected) return;
                const lang = config.lang;
                if (!window.confirm(lang.delete_confirm)) return;
                try {
                    await api(config.api + '/' + this.selected.id, { method: 'DELETE', body: JSON.stringify({}) });
                } catch (e) {
                    if (e.status !== 409 || !window.confirm(lang.delete_in_use.replace(':count', e.data.in_use))) return;
                    await api(config.api + '/' + this.selected.id, { method: 'DELETE', body: JSON.stringify({ force: true }) });
                }
                this.items = this.items.filter((i) => i.id !== this.selected.id);
                this.selected = null;
            },

            upload(fileList) {
                const files = Array.from(fileList || []);
                files.forEach((file) => {
                    const entry = { key: Math.random().toString(36).slice(2), name: file.name, progress: 0, done: false, error: '' };
                    this.uploads.unshift(entry);
                    const u = this.uploads[0];
                    if (!config.types.includes(file.type)) {
                        u.error = config.lang.limits.replace(':size', (config.maxKb / 1024).toFixed(1));
                        return;
                    }
                    if (file.size > config.maxKb * 1024) {
                        u.error = config.lang.limits.replace(':size', (config.maxKb / 1024).toFixed(1));
                        return;
                    }
                    const form = new FormData();
                    form.append('file', file);
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', config.api);
                    xhr.setRequestHeader('X-CSRF-TOKEN', csrf());
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.upload.onprogress = (e) => {
                        if (e.lengthComputable) u.progress = Math.round((e.loaded / e.total) * 100);
                    };
                    xhr.onload = () => {
                        let data = {};
                        try {
                            data = JSON.parse(xhr.responseText);
                        } catch (e) {}
                        if (xhr.status >= 200 && xhr.status < 300) {
                            u.done = true;
                            this.items.unshift(data.data);
                            this.tab = 'library';
                            this.select(data.data);
                        } else {
                            u.error = data.message || xhr.statusText;
                        }
                    };
                    xhr.onerror = () => (u.error = 'Network error');
                    xhr.send(form);
                });
            },

            insert() {
                if (!this.selected || !this.selected.alt) return;
                const editor = this.editor || findEditor();
                if (!editor) return;
                const size = (this.selected.sizes || []).find((s) => s.name === this.insertSize) || { url: this.selected.url, width: this.selected.width, height: this.selected.height };
                editor
                    .chain()
                    .focus()
                    .insertContent({
                        type: 'paragraph',
                        attrs: { textAlign: this.insertAlign },
                        content: [{ type: 'image', attrs: { src: size.url, alt: this.selected.alt, id: String(this.selected.id), width: size.width, height: size.height } }],
                    })
                    .run();
                this.close();
            },

            setFeatured() {
                if (!this.selected || !this.selected.alt) return;
                window.dispatchEvent(new CustomEvent('lwk-featured-selected', { detail: this.selected }));
                this.close();
            },

            mediaCredit(item) {
                return credit({ attribution: config.lang.pexels.attribution }, item.photographer, item.photographer_url, item.source_url);
            },

            pexelsCredit(photo) {
                return credit({ attribution: config.lang.pexels.attribution }, photo.photographer, photo.photographer_url, photo.url);
            },

            pexelsInit() {
                if (this.pexels.started) return;
                this.pexels.started = true;
                this.pexelsSuggest();
            },

            async pexelsSuggest() {
                const title = wireGet(this.$wire, 'data.title');
                if (!title) return;
                try {
                    const res = await api(config.pexels.suggest, { method: 'POST', body: JSON.stringify({ title }) });
                    this.pexels.q = res.query || title;
                    this.pexelsSearch(true);
                } catch (e) {
                    this.pexels.error = e.message;
                }
            },

            async pexelsSearch(reset) {
                if (!this.pexels.q) return;
                if (reset) {
                    this.pexels.page = 1;
                    this.pexels.photos = [];
                }
                this.pexels.error = '';
                const url = new URL(config.pexels.search, window.location.origin);
                url.searchParams.set('q', this.pexels.q);
                url.searchParams.set('page', this.pexels.page);
                if (this.pexels.orientation) url.searchParams.set('orientation', this.pexels.orientation);
                if (config.usable) {
                    url.searchParams.set('usable_type', config.usable.type);
                    url.searchParams.set('usable_id', config.usable.id);
                }
                try {
                    const res = await api(url.toString());
                    this.pexels.photos = this.pexels.photos.concat(res.photos);
                    this.pexels.hasMore = res.has_more;
                    this.pexels.page = res.page + 1;
                } catch (e) {
                    this.pexels.error = e.message;
                }
            },

            async pexelsImport() {
                const photo = this.pexels.selected;
                if (!photo) return;
                this.busy = true;
                this.pexels.error = '';
                try {
                    const body = { id: photo.id, ...(config.usable ? { usable_type: config.usable.type, usable_id: config.usable.id } : {}) };
                    const res = await api(config.pexels.import, { method: 'POST', body: JSON.stringify(body) });
                    photo.imported = true;
                    this.items.unshift(res.data);
                    this.pexels.selected = null;
                    this.selected = res.data;
                } catch (e) {
                    this.pexels.error = e.message;
                } finally {
                    this.busy = false;
                }
            },
        };
    };
})();

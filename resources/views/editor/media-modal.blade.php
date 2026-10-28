@php
    $admin = trim(config('larawebkit.admin_path'), '/');
    $modalConfig = [
        'api' => url("$admin/larawebkit/media"),
        'pexels' => $pexelsEnabled ? [
            'search' => url("$admin/larawebkit/pexels/search"),
            'import' => url("$admin/larawebkit/pexels/import"),
            'suggest' => url("$admin/larawebkit/pexels/suggest"),
        ] : null,
        'usable' => $record ? ['type' => $record->getMorphClass(), 'id' => $record->getKey()] : null,
        'canDelete' => auth()->user()?->can('delete media') ?? false,
        'maxKb' => (int) config('larawebkit-media.max_upload_kb'),
        'types' => (array) config('larawebkit-media.accepted_mime_types'),
        'sizes' => array_keys((array) config('larawebkit-media.sizes')),
        'lang' => trans('larawebkit-media.modal') + ['used_in' => trans('larawebkit-media.fields.used_in'), 'pexels' => trans('larawebkit-editor.pexels')],
    ];
@endphp

<div x-data="lwkMediaModal(@js($modalConfig))" @lwk-media-open.window="show($event.detail)" @keydown.escape.window="close()" x-cloak>
    <div class="lwk-modal-backdrop" x-show="open" x-transition.opacity @click="close()"></div>
    <div class="lwk-modal" x-show="open" x-transition role="dialog" aria-modal="true" aria-labelledby="lwk-media-title">
        <header class="lwk-modal-header">
            <h2 id="lwk-media-title">{{ __('larawebkit-media.modal.title') }}</h2>
            <nav class="lwk-tabs" role="tablist">
                <button type="button" role="tab" :aria-selected="tab === 'library'" :class="{ 'is-active': tab === 'library' }" @click="tab = 'library'">{{ __('larawebkit-media.modal.tab_library') }}</button>
                <button type="button" role="tab" :aria-selected="tab === 'upload'" :class="{ 'is-active': tab === 'upload' }" @click="tab = 'upload'">{{ __('larawebkit-media.modal.tab_upload') }}</button>
                <template x-if="config.pexels">
                    <button type="button" role="tab" :aria-selected="tab === 'pexels'" :class="{ 'is-active': tab === 'pexels' }" @click="tab = 'pexels'; pexelsInit()">{{ __('larawebkit-media.modal.tab_pexels') }}</button>
                </template>
            </nav>
            <button type="button" class="lwk-modal-close" @click="close()" aria-label="{{ __('larawebkit.actions.close') }}">×</button>
        </header>

        <div class="lwk-modal-body">
            <div class="lwk-modal-main">
                {{-- Library --}}
                <div x-show="tab === 'library'">
                    <input type="search" class="lwk-input" placeholder="{{ __('larawebkit-media.modal.search') }}" x-model.debounce.400ms="search" @input.debounce.400ms="load(true)">
                    <p class="lwk-muted" x-show="! loading && items.length === 0">{{ __('larawebkit-media.modal.empty') }}</p>
                    <ul class="lwk-grid">
                        <template x-for="item in items" :key="item.id">
                            <li>
                                <button type="button" class="lwk-tile" :class="{ 'is-selected': selected && selected.id === item.id }" @click="select(item)" :aria-pressed="selected && selected.id === item.id">
                                    <img :src="item.thumb" :alt="item.alt || item.file_name" loading="lazy" width="150" height="150">
                                </button>
                            </li>
                        </template>
                    </ul>
                    <button type="button" class="lwk-btn" x-show="hasMore" @click="load(false)" :disabled="loading">{{ __('larawebkit.actions.load_more') }}</button>
                </div>

                {{-- Upload --}}
                <div x-show="tab === 'upload'">
                    <label class="lwk-dropzone" :class="{ 'is-over': dragging }" @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dragging = false; upload($event.dataTransfer.files)">
                        <span>{{ __('larawebkit-media.modal.drop') }} <u>{{ __('larawebkit-media.modal.pick') }}</u></span>
                        <input type="file" multiple :accept="config.types.join(',')" class="lwk-sr-only" @change="upload($event.target.files); $event.target.value = ''">
                        <small class="lwk-muted">{{ __('larawebkit-media.modal.limits', ['size' => round(config('larawebkit-media.max_upload_kb') / 1024, 1)]) }}</small>
                    </label>
                    <ul class="lwk-uploads">
                        <template x-for="u in uploads" :key="u.key">
                            <li :class="u.error ? 'is-error' : (u.done ? 'is-done' : '')">
                                <span x-text="u.name"></span>
                                <progress max="100" :value="u.progress" x-show="! u.done && ! u.error"></progress>
                                <span class="lwk-error" x-show="u.error" x-text="u.error"></span>
                                <span x-show="u.done">✓</span>
                            </li>
                        </template>
                    </ul>
                </div>

                {{-- Pexels --}}
                <div x-show="tab === 'pexels'">
                    <form class="lwk-row" @submit.prevent="pexelsSearch(true)">
                        <input type="search" class="lwk-input" x-model="pexels.q" placeholder="{{ __('larawebkit-editor.pexels.query') }}">
                        <select class="lwk-input lwk-input-auto" x-model="pexels.orientation">
                            <option value="">{{ __('larawebkit-editor.pexels.any') }}</option>
                            <option value="landscape">{{ __('larawebkit-editor.pexels.landscape') }}</option>
                            <option value="portrait">{{ __('larawebkit-editor.pexels.portrait') }}</option>
                        </select>
                        <button type="submit" class="lwk-btn">{{ __('larawebkit.actions.search') }}</button>
                        <button type="button" class="lwk-btn" @click="pexelsSuggest()">{{ __('larawebkit-editor.pexels.suggest') }}</button>
                    </form>
                    <p class="lwk-error" x-show="pexels.error" x-text="pexels.error"></p>
                    <ul class="lwk-grid">
                        <template x-for="photo in pexels.photos" :key="photo.id">
                            <li>
                                <button type="button" class="lwk-tile" :class="{ 'is-selected': pexels.selected && pexels.selected.id === photo.id, 'is-used': photo.used_here }" :disabled="photo.used_here" @click="pexels.selected = photo; selected = null" :title="photo.used_here ? config.lang.pexels.already_used : photo.photographer">
                                    <img :src="photo.thumb" :alt="photo.alt" loading="lazy" width="150" height="150">
                                </button>
                            </li>
                        </template>
                    </ul>
                    <button type="button" class="lwk-btn" x-show="pexels.hasMore" @click="pexelsSearch(false)">{{ __('larawebkit.actions.load_more') }}</button>
                    <p class="lwk-small lwk-muted"><a href="https://www.pexels.com" target="_blank" rel="noopener">{{ __('larawebkit-editor.pexels.powered') }}</a></p>
                </div>
            </div>

            {{-- Details sidebar --}}
            <aside class="lwk-modal-side" aria-live="polite">
                <h3>{{ __('larawebkit-media.modal.details') }}</h3>
                <p class="lwk-muted" x-show="! selected && ! pexels.selected">{{ __('larawebkit-media.modal.select_prompt') }}</p>

                <template x-if="pexels.selected && ! selected">
                    <div>
                        <img :src="pexels.selected.thumb" :alt="pexels.selected.alt" class="lwk-preview">
                        <p class="lwk-small" x-html="pexelsCredit(pexels.selected)"></p>
                        <button type="button" class="lwk-btn lwk-btn-primary" @click="pexelsImport()" :disabled="busy">{{ __('larawebkit-editor.pexels.import') }}</button>
                    </div>
                </template>

                <template x-if="selected">
                    <div class="lwk-details">
                        <img :src="selected.sizes ? (selected.sizes.find(s => s.name === 'medium') || {}).url : selected.thumb" :alt="selected.alt || ''" class="lwk-preview">
                        <dl class="lwk-meta">
                            <dt>{{ __('larawebkit-media.fields.file_name') }}</dt><dd x-text="selected.file_name"></dd>
                            <dt>{{ __('larawebkit-media.fields.dimensions') }}</dt><dd x-text="selected.width + ' × ' + selected.height + ' px'"></dd>
                            <dt>{{ __('larawebkit-media.fields.size') }}</dt><dd x-text="selected.size_human || '…'"></dd>
                            <dt>{{ __('larawebkit-media.fields.uploaded') }}</dt><dd x-text="selected.created_human || '…'"></dd>
                            <dt>{{ __('larawebkit-media.fields.used_in') }}</dt><dd x-text="selected.used_in ?? '…'"></dd>
                        </dl>
                        <p class="lwk-small" x-show="selected.photographer" x-html="mediaCredit(selected)"></p>
                        <label class="lwk-label">{{ __('larawebkit-media.fields.title') }}<input class="lwk-input" x-model="selected.title" @change="saveMeta()"></label>
                        <label class="lwk-label">{{ __('larawebkit-media.fields.alt') }} *<input class="lwk-input" x-model="selected.alt" @change="saveMeta()" :aria-invalid="! selected.alt"></label>
                        <label class="lwk-label">{{ __('larawebkit-media.fields.caption') }}<textarea class="lwk-input" rows="2" x-model="selected.caption" @change="saveMeta()"></textarea></label>
                        <p class="lwk-small lwk-success" x-show="savedFlash">{{ __('larawebkit-media.modal.saved') }}</p>
                        <div class="lwk-row">
                            <button type="button" class="lwk-btn" @click="copyUrl()" x-text="copied ? @js(__('larawebkit.actions.copied')) : @js(__('larawebkit-media.modal.copy_url'))"></button>
                            <button type="button" class="lwk-btn lwk-btn-danger" x-show="config.canDelete" @click="destroy()">{{ __('larawebkit-media.modal.delete') }}</button>
                        </div>

                        <div x-show="mode === 'insert'">
                            <label class="lwk-label">{{ __('larawebkit-media.modal.size') }}
                                <select class="lwk-input" x-model="insertSize">
                                    <template x-for="s in config.sizes"><option :value="s" x-text="config.lang.sizes[s]"></option></template>
                                </select>
                            </label>
                            <label class="lwk-label">{{ __('larawebkit-media.modal.alignment') }}
                                <select class="lwk-input" x-model="insertAlign">
                                    <option value="start">{{ __('larawebkit-media.modal.align_start') }}</option>
                                    <option value="center">{{ __('larawebkit-media.modal.align_center') }}</option>
                                    <option value="end">{{ __('larawebkit-media.modal.align_end') }}</option>
                                </select>
                            </label>
                        </div>
                        <p class="lwk-error lwk-small" x-show="! selected.alt">{{ __('larawebkit-media.modal.alt_required') }}</p>
                        <div class="lwk-row">
                            <button type="button" class="lwk-btn lwk-btn-primary" x-show="mode === 'insert'" :disabled="! selected.alt" @click="insert()">{{ __('larawebkit-media.modal.insert') }}</button>
                            <button type="button" class="lwk-btn" :class="mode === 'featured' ? 'lwk-btn-primary' : ''" :disabled="! selected.alt" @click="setFeatured()">{{ __('larawebkit-media.modal.set_featured') }}</button>
                        </div>
                    </div>
                </template>
            </aside>
        </div>
    </div>
</div>

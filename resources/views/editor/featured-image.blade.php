<div wire:ignore x-data="lwkFeatured(@js(['api' => url(trim(config('larawebkit.admin_path'), '/').'/larawebkit/media'), 'attribution' => __('larawebkit-media.attribution')]))" class="lwk-featured">
    <template x-if="item">
        <div>
            <img :src="item.thumb" :alt="item.alt || ''" class="lwk-featured-img" width="300" height="300">
            <p class="lwk-small" x-show="! item.alt"><span class="lwk-error">{{ __('larawebkit-media.missing_alt') }}</span></p>
            <p class="lwk-small lwk-muted" x-show="item.photographer" x-html="credit()"></p>
        </div>
    </template>
    <div class="lwk-buttons">
        <button type="button" class="lwk-btn" @click="window.dispatchEvent(new CustomEvent('lwk-media-open', { detail: { mode: 'featured' } }))">
            <span x-text="item ? @js(__('larawebkit-editor.featured.replace')) : @js(__('larawebkit-editor.featured.choose'))"></span>
        </button>
        <button type="button" class="lwk-btn lwk-btn-danger" x-show="item" @click="remove()">{{ __('larawebkit-editor.featured.remove') }}</button>
    </div>
</div>

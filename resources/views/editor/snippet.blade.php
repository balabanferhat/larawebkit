<div wire:ignore x-data="lwkSnippet(@js(['prefix' => $prefix, 'site' => (string) setting('general.site_name'), 'separator' => (string) setting('seo.title_separator', '–'), 'min' => config('larawebkit-editor.seo.checks.meta_description.min', 120), 'max' => config('larawebkit-editor.seo.checks.meta_description.max', 160)]))" class="lwk-snippet-wrap">
    <p class="lwk-small">
        {{ __('larawebkit-editor.fields.meta_description') }}:
        <strong :class="countClass" x-text="count + ' / ' + max"></strong>
        <span class="lwk-muted">{{ __('larawebkit-editor.panel.meta_range', ['min' => config('larawebkit-editor.seo.checks.meta_description.min', 120), 'max' => config('larawebkit-editor.seo.checks.meta_description.max', 160)]) }}</span>
    </p>
    <div class="lwk-snippet" aria-label="{{ __('larawebkit-editor.panel.snippet') }}">
        <div class="lwk-snippet-url" x-text="url"></div>
        <div class="lwk-snippet-title" x-text="title"></div>
        <div class="lwk-snippet-desc" x-text="description"></div>
    </div>
</div>

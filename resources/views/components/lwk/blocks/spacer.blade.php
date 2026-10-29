<div @class(['lwk-spacer', 'lwk-spacer-'.(in_array($data['size'] ?? 'md', ['sm', 'md', 'lg'], true) ? ($data['size'] ?? 'md') : 'md')])>
    @if (! empty($data['divider']))<hr class="lwk-container">@endif
</div>

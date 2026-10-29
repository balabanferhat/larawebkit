@php
    $tokens = app(\App\Modules\Themes\Design\DesignTokens::class);
    $vars = $tokens->variables();
    $values = $tokens->values();
    $ratio = fn ($a, $b) => \App\Modules\Themes\Design\Color::hex($a)->contrast(\App\Modules\Themes\Design\Color::hex($b));
@endphp
<div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(16rem,1fr))">
    @foreach (['light', 'dark'] as $scheme)
        @php($v = $vars[$scheme])
        <div style="background:{{ $v['--bg'] }};color:{{ $v['--text'] }};border:1px solid {{ $v['--border'] }};border-radius:{{ $vars['light']['--radius'] }};padding:1rem 1.25rem;font-family:{{ $vars['light']['--font-body'] }}">
            <p style="margin:0 0 .25rem;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:{{ $v['--muted'] }}">{{ __("larawebkit-themes.appearance.$scheme") }}</p>
            <p style="margin:0 0 .5rem;font:700 1.35rem/1.2 {{ $vars['light']['--font-heading'] }}">{{ setting('general.site_name') }}</p>
            <p style="margin:0 0 .75rem">{{ __('larawebkit-themes.appearance.sample_text') }} <a href="#" style="color:{{ $v['--link'] }}">{{ __('larawebkit-themes.appearance.sample_link') }}</a></p>
            <span style="display:inline-block;background:{{ $v['--brand'] }};color:{{ $v['--on-brand'] }};padding:.45rem .9rem;border-radius:{{ $vars['light']['--radius-sm'] }};font-weight:600">{{ __('larawebkit-themes.appearance.sample_button') }}</span>
            <span style="display:inline-block;background:{{ $v['--accent'] }};color:{{ $v['--on-accent'] }};padding:.45rem .9rem;border-radius:{{ $vars['light']['--radius-sm'] }};font-weight:600;margin-left:.35rem">{{ __('larawebkit-themes.appearance.sample_accent') }}</span>
            <p style="margin:.75rem 0 0;font-size:.75rem;color:{{ $v['--muted'] }}">
                {{ __('larawebkit-themes.appearance.contrast', ['link' => $ratio($v['--link'], $v['--bg']), 'button' => $ratio($v['--on-brand'], $v['--brand'])]) }}
            </p>
        </div>
    @endforeach
</div>
<p style="margin-top:.75rem;font-size:.85rem;opacity:.8">{{ __('larawebkit-themes.appearance.resolved', ['primary' => $values['primary'], 'accent' => $values['accent'], 'fonts' => __('larawebkit-themes.fonts.'.$values['font_pair']), 'radius' => __('larawebkit-themes.radius.'.$values['radius'])]) }}</p>

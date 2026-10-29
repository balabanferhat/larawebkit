{{-- Shared <head> assets for every theme: design tokens from Appearance, self-hosted fonts, favicons, manifest and the colour-scheme bootstrap (runs before paint, so there is no flash). --}}
@php
    $tokens = app(\App\Modules\Themes\Design\DesignTokens::class);
    $design = $tokens->values();
    $vars = $tokens->variables();
@endphp
<meta name="theme-color" content="{{ $vars['light']['--bg'] }}" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="{{ $vars['dark']['--bg'] }}" media="(prefers-color-scheme: dark)">
<script>(function(){var d=document.documentElement,s=@js($design['color_scheme']),t=null;try{t=localStorage.getItem('lwk-theme')}catch(e){}if(t!=='light'&&t!=='dark'){t=s==='system'?(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'):s}d.dataset.theme=t;d.classList.add('js')})();</script>
{{ \App\Modules\Themes\Design\Fonts::preloads($design['font_pair']) }}
<style id="lwk-tokens">{!! $tokens->css() !!}</style>
@themeStyles
{{ app(\App\Modules\Themes\Design\Favicons::class)->tags() }}
<link rel="manifest" href="{{ url('/site.webmanifest') }}">

{{-- Site logo (separate dark-mode logo optional) or the site name. --}}
@php
    $name = (string) setting('general.site_name');
    $logo = ($id = setting('appearance.logo')) ? \App\Modules\Media\Models\Media::find($id) : null;
    $dark = ($id = setting('appearance.logo_dark')) ? \App\Modules\Media\Models\Media::find($id) : null;
@endphp
<a href="{{ localized_path('/') }}" {{ $attributes->merge(['class' => 'lwk-brand']) }} rel="home">
    @if ($logo)
        <x-lwk.image :media="$logo" :alt="$name" size="480" sizes="200px" loading="eager" decoding="async" :class="$dark ? 'lwk-logo lwk-logo-light' : 'lwk-logo'" />
        @if ($dark)<x-lwk.image :media="$dark" :alt="$name" size="480" sizes="200px" loading="eager" decoding="async" class="lwk-logo lwk-logo-dark" />@endif
    @else
        <span class="lwk-brand-name">{{ $name }}</span>
    @endif
</a>

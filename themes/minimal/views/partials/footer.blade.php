@php
    $email = setting('general.contact_email');
    $phone = setting('organization.phone');
    $address = setting('organization.address');
    $showContact = setting('footer.show_contact', true) && ($email || $phone || $address);
    $columns = collect(['footer', 'footer_secondary'])->filter(fn ($l) => \App\Modules\Core\ModuleRegistry::enabled('content') && \App\Modules\Content\Models\Menu::for($l)->isNotEmpty());
@endphp
<footer class="lwk-footer">
    <div class="lwk-container lwk-footer-grid">
        <div class="lwk-footer-about">
            <x-lwk.brand />
            @if ($text = setting('footer.text') ?: setting('general.tagline'))<p>{{ $text }}</p>@endif
            <x-lwk.social />
        </div>
        @foreach ($columns as $location)
            <div class="lwk-footer-col">
                <h2 class="lwk-footer-title">{{ \App\Modules\Content\Models\Menu::label($location) }}</h2>
                <x-lwk.menu :location="$location" :dropdowns="false" />
            </div>
        @endforeach
        @if ($showContact)
            <div class="lwk-footer-col">
                <h2 class="lwk-footer-title">{{ __('larawebkit-front.footer.contact') }}</h2>
                <address class="lwk-contact-list">
                    @if ($address)<p>{{ \App\Modules\Themes\Support\Icons::ui('map-pin') }}<span>{!! nl2br(e($address)) !!}</span></p>@endif
                    @if ($phone)<p>{{ \App\Modules\Themes\Support\Icons::ui('phone') }}<a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">{{ $phone }}</a></p>@endif
                    @if ($email)<p>{{ \App\Modules\Themes\Support\Icons::ui('envelope') }}<a href="mailto:{{ $email }}">{{ $email }}</a></p>@endif
                </address>
            </div>
        @endif
        <x-lwk.newsletter class="lwk-footer-newsletter" />
    </div>
    <div class="lwk-container lwk-footer-bottom">
        <p>{{ setting('footer.copyright') ?: __('larawebkit-front.footer.copyright', ['year' => date('Y'), 'name' => setting('general.site_name')]) }}</p>
        <x-lwk.menu location="legal" :dropdowns="false" :label="__('larawebkit-front.footer.legal')" />
        <p class="lwk-footer-links">
            <a href="{{ url('/feed.xml') }}">{{ __('larawebkit-front.rss') }}</a>
            @if (\App\Modules\Core\ModuleRegistry::enabled('privacy') && setting('privacy.consent_enabled', true)) · <a href="#" data-open-consent>{{ __('larawebkit-privacy.banner.settings') }}</a>@endif
        </p>
    </div>
</footer>

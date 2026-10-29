{{-- Personal brand: the newsletter comes first, as a wide band above a light footer. --}}
@if (\App\Modules\Core\ModuleRegistry::enabled('newsletter') && setting('features.newsletter', false) && ! request()->routeIs('front.home', 'front.localized.home'))
    <section class="lwk-newsletter-band" aria-label="{{ __('larawebkit-front.newsletter.title') }}">
        <div class="lwk-container">
            <x-lwk.newsletter :fallback-title="__('theme-personal::theme.newsletter_title')" :fallback-text="__('theme-personal::theme.newsletter_text')" />
        </div>
    </section>
@endif
<footer class="lwk-footer">
    <div class="lwk-container lwk-footer-personal">
        <x-lwk.brand />
        @if ($text = setting('footer.text') ?: setting('general.tagline'))<p class="lwk-footer-about-text">{{ $text }}</p>@endif
        <x-lwk.menu location="footer" :dropdowns="false" />
        <x-lwk.social />
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

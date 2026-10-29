{{-- title/text: explicit (a block) → Settings → Newsletter → sign-up box → the theme's fallback → neutral default. --}}
@props(['title' => null, 'text' => null, 'button' => null, 'fallbackTitle' => null, 'fallbackText' => null])
@if (\App\Modules\Core\ModuleRegistry::enabled('newsletter') && setting('features.newsletter', false))
    @php($id = 'newsletter-email-'.substr(md5(uniqid('', true)), 0, 6))
    @php($consent = (bool) setting('newsletter.require_consent', false))
    <form method="POST" action="{{ route('newsletter.subscribe') }}" {{ $attributes->merge(['class' => 'lwk-newsletter']) }}>
        @csrf
        <x-lwk.honeypot />
        <p class="lwk-newsletter-title">{{ $title ?: (setting('newsletter.band_title') ?: ($fallbackTitle ?: __('larawebkit-front.newsletter.title'))) }}</p>
        <p class="lwk-newsletter-text">{{ $text ?: (setting('newsletter.band_text') ?: ($fallbackText ?: __('larawebkit-front.newsletter.text'))) }}</p>
        <label for="{{ $id }}" class="lwk-sr-only">{{ __('larawebkit-front.newsletter.email') }}</label>
        <div class="lwk-inline-form">
            <input id="{{ $id }}" type="email" name="email" required autocomplete="email" placeholder="{{ __('larawebkit-front.newsletter.email') }}">
            <button type="submit" class="lwk-button">{{ $button ?: __('larawebkit-front.newsletter.subscribe') }}</button>
        </div>
        @if ($consent)
            <label class="lwk-choice lwk-newsletter-consent">
                <input type="checkbox" name="consent" value="1" required>
                <span>{{ \App\Modules\Newsletter\Models\Subscriber::consentText() }} <a href="{{ localized_path('/privacy-policy') }}">{{ __('larawebkit-front.newsletter.privacy') }}</a></span>
            </label>
        @endif
        @error('consent')<p class="lwk-error">{{ $message }}</p>@enderror
    </form>
@endif

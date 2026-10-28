@if (\App\Modules\Core\ModuleRegistry::enabled('newsletter') && setting('features.newsletter', false))
    <form method="POST" action="{{ route('newsletter.subscribe') }}" class="lwk-newsletter">
        @csrf
        <x-lwk.honeypot />
        <p class="lwk-newsletter-title">{{ __('larawebkit-front.newsletter.title') }}</p>
        <p>{{ __('larawebkit-front.newsletter.text') }}</p>
        <label for="newsletter-email" class="lwk-sr-only">{{ __('larawebkit-front.newsletter.email') }}</label>
        <div class="lwk-inline-form">
            <input id="newsletter-email" type="email" name="email" required autocomplete="email" placeholder="{{ __('larawebkit-front.newsletter.email') }}">
            <button type="submit">{{ __('larawebkit-front.newsletter.subscribe') }}</button>
        </div>
    </form>
@endif

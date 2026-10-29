@props(['title' => null, 'text' => null, 'button' => null])
@if (\App\Modules\Core\ModuleRegistry::enabled('newsletter') && setting('features.newsletter', false))
    @php($id = 'newsletter-email-'.substr(md5(uniqid('', true)), 0, 6))
    <form method="POST" action="{{ route('newsletter.subscribe') }}" {{ $attributes->merge(['class' => 'lwk-newsletter']) }}>
        @csrf
        <x-lwk.honeypot />
        <p class="lwk-newsletter-title">{{ $title ?: __('larawebkit-front.newsletter.title') }}</p>
        <p class="lwk-newsletter-text">{{ $text ?: __('larawebkit-front.newsletter.text') }}</p>
        <label for="{{ $id }}" class="lwk-sr-only">{{ __('larawebkit-front.newsletter.email') }}</label>
        <div class="lwk-inline-form">
            <input id="{{ $id }}" type="email" name="email" required autocomplete="email" placeholder="{{ __('larawebkit-front.newsletter.email') }}">
            <button type="submit" class="lwk-button">{{ $button ?: __('larawebkit-front.newsletter.subscribe') }}</button>
        </div>
    </form>
@endif

{{-- Messages after a redirect (newsletter confirmation, unsubscribe, account deletion...). --}}
@if (session('status') && ! View::hasSection('no_flash'))
    <div class="lwk-flash" role="status">
        <div class="lwk-container lwk-flash-inner">
            <p>{{ session('status') }}</p>
            @if (session('download'))<a href="{{ session('download') }}" class="lwk-button">{{ __('larawebkit-forms.mail.download') }}</a>@endif
        </div>
    </div>
@endif

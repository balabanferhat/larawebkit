{{-- The built-in contact form (Contact module). Used by the contact page template and the "form" block. --}}
@if (\App\Modules\Core\ModuleRegistry::enabled('contact') && setting('features.contact_form', true))
    @if (session('status'))
        <p class="lwk-notice" role="status">{{ session('status') }}</p>
    @endif
    @php($uid = 'c'.substr(md5(uniqid('', true)), 0, 5))
    <form method="POST" action="{{ route('contact.send') }}" class="lwk-form" novalidate>
        @csrf
        <x-lwk.honeypot />
        <div class="lwk-form-row">
            <div class="lwk-field">
                <label for="{{ $uid }}-name">{{ __('larawebkit-front.contact.name') }}</label>
                <input id="{{ $uid }}-name" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name" @error('name') aria-invalid="true" aria-describedby="{{ $uid }}-name-error" @enderror>
                @error('name')<p class="lwk-error" id="{{ $uid }}-name-error">{{ $message }}</p>@enderror
            </div>
            <div class="lwk-field">
                <label for="{{ $uid }}-email">{{ __('larawebkit-front.contact.email') }}</label>
                <input id="{{ $uid }}-email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" @error('email') aria-invalid="true" aria-describedby="{{ $uid }}-email-error" @enderror>
                @error('email')<p class="lwk-error" id="{{ $uid }}-email-error">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="lwk-field">
            <label for="{{ $uid }}-subject">{{ __('larawebkit-front.contact.subject') }}</label>
            <input id="{{ $uid }}-subject" name="subject" value="{{ old('subject') }}" maxlength="200">
        </div>
        <div class="lwk-field">
            <label for="{{ $uid }}-message">{{ __('larawebkit-front.contact.message') }}</label>
            <textarea id="{{ $uid }}-message" name="message" rows="6" required maxlength="5000" @error('message') aria-invalid="true" aria-describedby="{{ $uid }}-message-error" @enderror>{{ old('message') }}</textarea>
            @error('message')<p class="lwk-error" id="{{ $uid }}-message-error">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="lwk-button">{{ __('larawebkit-front.contact.send') }}</button>
    </form>
@else
    <p>{{ __('larawebkit-front.contact.disabled') }}</p>
@endif

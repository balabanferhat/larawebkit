@extends(app(\App\Modules\Themes\ThemeManager::class)->view('page'))

@section('after-content')
    @if (\App\Modules\Core\ModuleRegistry::enabled('contact') && setting('features.contact_form', true))
        @if (session('status'))
            <p class="lwk-notice" role="status">{{ session('status') }}</p>
        @endif
        <form method="POST" action="{{ route('contact.send') }}" class="lwk-form" novalidate>
            @csrf
            <x-lwk.honeypot />
            <label>{{ __('larawebkit-front.contact.name') }}<input name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name"></label>
            @error('name')<p class="lwk-error">{{ $message }}</p>@enderror
            <label>{{ __('larawebkit-front.contact.email') }}<input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></label>
            @error('email')<p class="lwk-error">{{ $message }}</p>@enderror
            <label>{{ __('larawebkit-front.contact.subject') }}<input name="subject" value="{{ old('subject') }}" maxlength="200"></label>
            <label>{{ __('larawebkit-front.contact.message') }}<textarea name="message" rows="6" required maxlength="5000">{{ old('message') }}</textarea></label>
            @error('message')<p class="lwk-error">{{ $message }}</p>@enderror
            <button type="submit">{{ __('larawebkit-front.contact.send') }}</button>
        </form>
    @else
        <p>{{ __('larawebkit-front.contact.disabled') }}</p>
    @endif
@endsection

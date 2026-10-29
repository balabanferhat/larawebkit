@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

@section('no_flash', '1')

@section('content')
    <div class="lwk-container lwk-account">
        <header class="lwk-listing-head">
            <h1>{{ $title }}</h1>
            <p class="lwk-lead">{{ __('larawebkit-members.account.hello', ['name' => $member->name]) }}</p>
            @if (session('status'))<p class="lwk-notice lwk-notice-success" role="status">{{ session('status') }}</p>@endif
            @unless ($member->hasVerifiedEmail())
                <p class="lwk-notice" role="note">{{ __('larawebkit-members.account.unverified') }} <a href="{{ route('members.verification.notice') }}">{{ __('larawebkit-members.verify.resend') }}</a></p>
            @endunless
        </header>

        @stack('account_sections')

        <section class="lwk-account-section" aria-labelledby="acc-profile">
            <h2 id="acc-profile">{{ __('larawebkit-members.account.profile') }}</h2>
            <form method="POST" action="{{ route('members.account.update') }}" class="lwk-form">
                @csrf @method('PUT')
                @include('theme::account._field', ['name' => 'name', 'label' => __('larawebkit-members.fields.name'), 'value' => $member->name, 'autocomplete' => 'name'])
                @include('theme::account._field', ['name' => 'email', 'type' => 'email', 'label' => __('larawebkit-members.fields.email'), 'value' => $member->email, 'autocomplete' => 'email'])
                <button type="submit" class="lwk-button">{{ __('larawebkit-members.account.save') }}</button>
            </form>
        </section>

        <section class="lwk-account-section" aria-labelledby="acc-password">
            <h2 id="acc-password">{{ __('larawebkit-members.account.password') }}</h2>
            <form method="POST" action="{{ route('members.account.password') }}" class="lwk-form">
                @csrf @method('PUT')
                @include('theme::account._field', ['name' => 'current_password', 'type' => 'password', 'label' => __('larawebkit-members.fields.password_current'), 'autocomplete' => 'current-password'])
                @include('theme::account._field', ['name' => 'password', 'type' => 'password', 'label' => __('larawebkit-members.fields.password_new'), 'autocomplete' => 'new-password'])
                @include('theme::account._field', ['name' => 'password_confirmation', 'type' => 'password', 'label' => __('larawebkit-members.fields.password_confirm'), 'autocomplete' => 'new-password'])
                <button type="submit" class="lwk-button">{{ __('larawebkit-members.account.change_password') }}</button>
            </form>
        </section>

        <section class="lwk-account-section lwk-account-danger" aria-labelledby="acc-delete">
            <h2 id="acc-delete">{{ __('larawebkit-members.account.delete') }}</h2>
            <p>{{ __('larawebkit-members.account.delete_text') }}</p>
            <form method="POST" action="{{ route('members.account.destroy') }}" class="lwk-form">
                @csrf @method('DELETE')
                @include('theme::account._field', ['name' => 'password', 'type' => 'password', 'label' => __('larawebkit-members.fields.password_current'), 'autocomplete' => 'current-password'])
                <button type="submit" class="lwk-button lwk-button-danger">{{ __('larawebkit-members.account.delete_button') }}</button>
            </form>
        </section>

        <form method="POST" action="{{ route('members.logout') }}" class="lwk-account-logout">@csrf<button type="submit" class="lwk-button lwk-button-secondary">{{ __('larawebkit-members.logout') }}</button></form>
    </div>
@endsection

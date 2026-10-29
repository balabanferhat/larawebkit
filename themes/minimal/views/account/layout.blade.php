@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

@section('no_flash', '1')

@section('content')
    <div class="lwk-container lwk-auth">
        <div class="lwk-auth-card">
            <h1>{{ $title }}</h1>
            @if (session('status'))<p class="lwk-notice lwk-notice-success" role="status">{{ session('status') }}</p>@endif
            @yield('auth')
        </div>
        @hasSection('auth_after')<div class="lwk-auth-after">@yield('auth_after')</div>@endif
    </div>
@endsection

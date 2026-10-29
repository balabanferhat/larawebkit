@extends(app(\App\Modules\Themes\ThemeManager::class)->view('page'))

@section('after-content')
    <div class="lwk-contact-form">@include('components.lwk.forms.contact')</div>
@endsection

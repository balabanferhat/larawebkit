{{-- Renders an admin-defined form (Forms module) by slug: $formKey, or the "form" prop. --}}
@php
    $slug = $formKey ?? ($form ?? 'contact');
    $definition = \App\Modules\Core\ModuleRegistry::enabled('forms') ? \App\Modules\Forms\Models\Form::where('slug', $slug)->where('is_active', true)->first() : null;
    $uid = 'f'.substr(md5(uniqid('', true)), 0, 5);
    $status = session('form_status.'.$slug);
    $old = fn ($key, $default = null) => old('fields.'.$key, $default);
    $err = fn ($key) => $errors->first('fields.'.$key);
@endphp
@if (! $definition)
    <p class="lwk-meta">{{ __('larawebkit-forms.unavailable') }}</p>
@else
    <div id="form-{{ $definition->slug }}" class="lwk-form-anchor"></div>
    @if ($status)
        <p class="lwk-notice lwk-notice-success" role="status">{{ $status }}</p>
    @else
        <form method="POST" action="{{ route('forms.submit', $definition->slug) }}" class="lwk-form" @if (collect($definition->fieldList())->contains('type', 'file')) enctype="multipart/form-data" @endif novalidate>
            @csrf
            <x-lwk.honeypot />
            <div class="lwk-form-grid">
                @foreach ($definition->fieldList() as $field)
                    @php
                        $id = $uid.'-'.$field['key'];
                        $name = 'fields['.$field['key'].']';
                        $required = ! empty($field['required']) || $field['type'] === 'consent';
                        $error = $err($field['key']);
                        $described = trim(($error ? $id.'-error ' : '').(filled($field['help'] ?? null) ? $id.'-help' : ''));
                    @endphp
                    <div @class(['lwk-field', 'is-half' => ($field['width'] ?? 'full') === 'half', 'is-check' => in_array($field['type'], ['checkbox', 'consent'], true)])>
                        @switch($field['type'])
                            @case('textarea')
                                <label for="{{ $id }}">{{ $field['label'] }}@if ($required)<span aria-hidden="true"> *</span>@endif</label>
                                <textarea id="{{ $id }}" name="{{ $name }}" rows="5" @if ($required) required @endif maxlength="{{ (int) ($field['max'] ?? 5000) }}" placeholder="{{ $field['placeholder'] ?? '' }}" @if ($error) aria-invalid="true" @endif @if ($described) aria-describedby="{{ $described }}" @endif>{{ $old($field['key']) }}</textarea>
                                @break
                            @case('select')
                                <label for="{{ $id }}">{{ $field['label'] }}@if ($required)<span aria-hidden="true"> *</span>@endif</label>
                                <select id="{{ $id }}" name="{{ $name }}" @if ($required) required @endif @if ($error) aria-invalid="true" @endif @if ($described) aria-describedby="{{ $described }}" @endif>
                                    <option value="">{{ $field['placeholder'] ?? __('larawebkit-forms.choose') }}</option>
                                    @foreach (\App\Modules\Forms\FieldTypes::options($field) as $option)<option @selected($old($field['key']) === $option)>{{ $option }}</option>@endforeach
                                </select>
                                @break
                            @case('radio')
                                <fieldset @if ($described) aria-describedby="{{ $described }}" @endif>
                                    <legend>{{ $field['label'] }}@if ($required)<span aria-hidden="true"> *</span>@endif</legend>
                                    @foreach (\App\Modules\Forms\FieldTypes::options($field) as $i => $option)
                                        <label class="lwk-choice"><input type="radio" name="{{ $name }}" value="{{ $option }}" @checked($old($field['key']) === $option) @if ($required && $i === 0) required @endif> {{ $option }}</label>
                                    @endforeach
                                </fieldset>
                                @break
                            @case('checkbox')
                            @case('consent')
                                <label class="lwk-choice" for="{{ $id }}">
                                    <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1" @checked($old($field['key'])) @if ($required) required @endif @if ($error) aria-invalid="true" @endif @if ($described) aria-describedby="{{ $described }}" @endif>
                                    <span>{{ $field['label'] }}@if ($field['type'] === 'consent' && filled($field['link_url'] ?? null)) <a href="{{ $field['link_url'] }}" target="_blank" rel="noopener">{{ $field['link_label'] ?? __('larawebkit-forms.read_more') }}</a>@endif</span>
                                </label>
                                @break
                            @case('file')
                                <label for="{{ $id }}">{{ $field['label'] }}@if ($required)<span aria-hidden="true"> *</span>@endif</label>
                                <input type="file" id="{{ $id }}" name="{{ $name }}" accept="{{ collect(\App\Modules\Forms\FieldTypes::accept($field))->map(fn ($e) => '.'.$e)->implode(',') }}" @if ($required) required @endif @if ($error) aria-invalid="true" @endif @if ($described) aria-describedby="{{ $described }}" @endif>
                                <p class="lwk-help">{{ __('larawebkit-forms.file_hint', ['types' => implode(', ', \App\Modules\Forms\FieldTypes::accept($field)), 'mb' => round(((int) ($field['max_kb'] ?? 5120)) / 1024, 1)]) }}</p>
                                @break
                            @default
                                <label for="{{ $id }}">{{ $field['label'] }}@if ($required)<span aria-hidden="true"> *</span>@endif</label>
                                <input id="{{ $id }}" name="{{ $name }}" value="{{ $old($field['key']) }}" placeholder="{{ $field['placeholder'] ?? '' }}"
                                    type="{{ ['email' => 'email', 'phone' => 'tel', 'date' => 'date'][$field['type']] ?? 'text' }}"
                                    @if ($field['type'] === 'email') autocomplete="email" @elseif ($field['type'] === 'phone') autocomplete="tel" @elseif ($field['key'] === 'name') autocomplete="name" @endif
                                    @if ($required) required @endif @if ($error) aria-invalid="true" @endif @if ($described) aria-describedby="{{ $described }}" @endif>
                        @endswitch
                        @if (filled($field['help'] ?? null))<p class="lwk-help" id="{{ $id }}-help">{{ $field['help'] }}</p>@endif
                        @if ($error)<p class="lwk-error" id="{{ $id }}-error">{{ $error }}</p>@endif
                    </div>
                @endforeach
            </div>
            <button type="submit" class="lwk-button">{{ $definition->submit_label ?: __('larawebkit-forms.submit') }}</button>
        </form>
    @endif
@endif

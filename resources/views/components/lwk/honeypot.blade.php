@php($hp = config('larawebkit-security.honeypot'))
<div aria-hidden="true" style="position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden">
    <label for="{{ $hp['field'] }}">Website</label>
    <input type="text" name="{{ $hp['field'] }}" id="{{ $hp['field'] }}" value="" tabindex="-1" autocomplete="off">
</div>
<input type="hidden" name="{{ $hp['time_field'] }}" value="{{ \App\Modules\Security\Http\Middleware\ProtectPublicForm::timestamp() }}">

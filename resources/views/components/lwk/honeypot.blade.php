@php($hp = config('larawebkit-security.honeypot'))
@php($uid = $hp['field'].'-'.substr(md5(uniqid('', true)), 0, 6))
<div aria-hidden="true" style="position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden">
    <label for="{{ $uid }}">Website</label>
    <input type="text" name="{{ $hp['field'] }}" id="{{ $uid }}" value="" tabindex="-1" autocomplete="off">
</div>
<input type="hidden" name="{{ $hp['time_field'] }}" value="{{ \App\Modules\Security\Http\Middleware\ProtectPublicForm::timestamp() }}">

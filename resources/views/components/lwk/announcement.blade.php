{{-- Optional announcement bar (Appearance → Header). Dismissal is remembered per text, so a new announcement shows again. --}}
@php($text = trim((string) setting('header.announcement')))
@if ($text !== '')
    @php($key = substr(md5($text), 0, 8))
    <aside class="lwk-announcement" data-announcement="{{ $key }}" aria-label="{{ __('larawebkit-front.announcement') }}">
        <div class="lwk-container lwk-announcement-inner">
            <p>
                @if ($url = setting('header.announcement_url'))
                    <a href="{{ $url }}">{{ $text }} <span aria-hidden="true">→</span></a>
                @else
                    {{ $text }}
                @endif
            </p>
            <button type="button" class="lwk-announcement-close" data-dismiss-announcement aria-label="{{ __('larawebkit-front.nav.dismiss') }}">{{ \App\Modules\Themes\Support\Icons::ui('x-mark') }}</button>
        </div>
    </aside>
    <script>try{if(localStorage.getItem('lwk-ann')==='{{ $key }}')document.querySelector('[data-announcement="{{ $key }}"]').hidden=true}catch(e){}</script>
@endif

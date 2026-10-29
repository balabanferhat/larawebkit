@php($members = array_values(array_filter((array) ($data['members'] ?? []), fn ($m) => filled($m['name'] ?? null))))
<div class="lwk-container">
    @include('components.lwk.blocks.partials.head', ['center' => true])
    <ul class="lwk-team" role="list">
        @foreach ($members as $member)
            @php($photo = $media[$member['photo'] ?? 0] ?? null)
            <li class="lwk-person">
                @if ($photo)<x-lwk.image :media="$photo" size="640" sizes="(min-width: 960px) 20vw, 50vw" alt="" class="lwk-person-photo" />@else<span class="lwk-person-photo lwk-initials" aria-hidden="true">{{ mb_substr($member['name'], 0, 1) }}</span>@endif
                <h3>@if (filled($member['url'] ?? null))<a href="{{ $member['url'] }}" rel="noopener">{{ $member['name'] }}</a>@else{{ $member['name'] }}@endif</h3>
                @if (filled($member['role'] ?? null))<p class="lwk-meta">{{ $member['role'] }}</p>@endif
                @if ($variant === 'cards' && filled($member['bio'] ?? null))<p>{{ $member['bio'] }}</p>@endif
            </li>
        @endforeach
    </ul>
</div>

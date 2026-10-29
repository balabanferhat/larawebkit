@php
    $record = $getRecord();
    $ai = app(\App\Modules\Ai\AiManager::class);
    $pexels = app(\App\Modules\Editor\Pexels\PexelsClient::class);
    $user = auth()->user();
    $aiOn = $ai->enabled() && $user?->can('use ai');
    $config = [
        'scoring' => \App\Modules\Editor\ContentScorer::clientConfig(),
        'autosaveSeconds' => (int) config('larawebkit-editor.autosave_seconds', 30),
        'canAutosave' => (bool) $record,
        'ai' => [
            'enabled' => $aiOn,
            'overBudget' => $aiOn && $ai->overBudget(),
            'run' => url(trim(config('larawebkit.admin_path'), '/').'/larawebkit/ai'),
        ],
        'lang' => $aiOn ? trans('larawebkit-ai.assistant') : [],
        'editorLang' => trans('larawebkit-editor.panel'),
    ];
@endphp

<div wire:ignore x-data="lwkEditorPanel(@js($config))" class="lwk-panel">
    {{-- Scores --}}
    <section class="lwk-card">
        <div class="lwk-scores">
            <template x-for="panel in panels" :key="panel.key">
                <button type="button" class="lwk-score" :class="['lwk-score-' + level(panel.result.score), open === panel.key ? 'is-open' : '']" @click="open = open === panel.key ? null : panel.key">
                    <span class="lwk-score-value" x-text="panel.result.score"></span>
                    <span class="lwk-score-label" x-text="panel.label"></span>
                </button>
            </template>
        </div>
        <p class="lwk-muted lwk-small">
            <span x-text="words"></span> {{ __('larawebkit-editor.panel.words') }} ·
            <span x-text="readingTime"></span> {{ __('larawebkit-editor.panel.minutes') }}
        </p>

        <template x-for="panel in panels" :key="'checks-' + panel.key">
            <ul class="lwk-checks" x-show="open === panel.key" x-cloak>
                <template x-for="check in panel.result.checks" :key="check.key">
                    <li :class="check.passed ? 'is-pass' : 'is-fail'">
                        <span class="lwk-check-icon" x-text="check.passed ? '✓' : '✗'"></span>
                        <span x-text="check.hint"></span>
                    </li>
                </template>
            </ul>
        </template>

        <div class="lwk-gate" x-show="gate.enabled" :class="gateFailures.length ? 'is-blocked' : 'is-ok'">
            <strong>{{ __('larawebkit-editor.panel.gate') }}:</strong>
            <span x-show="! gateFailures.length">{{ __('larawebkit-editor.panel.gate_ok') }}</span>
            <ul x-show="gateFailures.length"><template x-for="f in gateFailures"><li x-text="f"></li></template></ul>
        </div>

        @if ($record)
            <p class="lwk-small lwk-muted">
                {{ __('larawebkit-editor.panel.saved_scores') }}:
                SEO {{ $record->seo_score ?? '—' }} · {{ __('larawebkit-editor.panel.human') }} {{ $record->human_score ?? '—' }} · GEO {{ $record->geo_score ?? '—' }}
            </p>
        @endif
        <p class="lwk-small lwk-muted" x-show="config.canAutosave" x-text="autosaveText"></p>
        <p class="lwk-small lwk-muted" x-show="! config.canAutosave">{{ __('larawebkit-editor.autosave.after_first_save') }}</p>
    </section>

    {{-- AI assistant --}}
    @if ($aiOn)
        <section class="lwk-card">
            <h3 class="lwk-card-title">{{ __('larawebkit-ai.assistant.title') }}</h3>
            @if ($ai->overBudget())
                <p class="lwk-error">{{ __('larawebkit-ai.errors.budget') }}</p>
            @else
                <textarea class="lwk-input" rows="2" x-model="brief" placeholder="{{ __('larawebkit-ai.assistant.brief') }}"></textarea>
                <div class="lwk-buttons">
                    <button type="button" class="lwk-btn" @click="aiDraft()" :disabled="busy">{{ __('larawebkit-ai.assistant.generate_draft') }}</button>
                    <button type="button" class="lwk-btn" @click="aiRewrite()" :disabled="busy">{{ __('larawebkit-ai.assistant.rewrite_selection') }}</button>
                    <button type="button" class="lwk-btn" @click="aiMeta()" :disabled="busy">{{ __('larawebkit-ai.assistant.suggest_meta') }}</button>
                    @if ($type === 'post')
                        <button type="button" class="lwk-btn" @click="aiTags()" :disabled="busy">{{ __('larawebkit-ai.assistant.suggest_tags') }}</button>
                    @endif
                    <button type="button" class="lwk-btn" @click="aiTopics()" :disabled="busy">{{ __('larawebkit-ai.assistant.suggest_topics') }}</button>
                </div>
                <p class="lwk-small lwk-muted" x-show="aiStatus" x-text="aiStatus"></p>
                <pre class="lwk-ai-output" x-show="aiOutput" x-text="aiOutput"></pre>
            @endif
        </section>
    @endif

    @include('editor.media-modal', ['pexelsEnabled' => $pexels->enabled(), 'record' => $record, 'type' => $type])
</div>

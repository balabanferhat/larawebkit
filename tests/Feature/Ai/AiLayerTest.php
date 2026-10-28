<?php

use App\Models\User;
use App\Modules\Ai\AiManager;
use App\Modules\Ai\Drivers\AiDriver;
use App\Modules\Ai\Drivers\AiResponse;
use App\Modules\Ai\Models\AiTask;
use App\Modules\Ai\Models\AiUsage;
use App\Modules\Ai\PromptRepository;
use App\Modules\Content\Filament\Resources\Posts\PostResource;
use App\Modules\Settings\SettingsRepository;
use Spatie\Permission\Models\Role;

class FakeAiDriver implements AiDriver
{
    public array $calls = [];

    public function __construct(public string|Throwable $reply = 'ok') {}

    public function complete(string $system, string $prompt, string $model, int $maxTokens): AiResponse
    {
        $this->calls[] = compact('system', 'prompt', 'model', 'maxTokens');
        if ($this->reply instanceof Throwable) {
            throw $this->reply;
        }

        return new AiResponse($this->reply, 1000, 500, $model);
    }
}

function fakeAi(string $provider = 'anthropic', string|Throwable $reply = 'ok'): FakeAiDriver
{
    config(["larawebkit-ai.providers.$provider.api_key" => 'test-key']);
    $driver = new FakeAiDriver($reply);
    app(AiManager::class)->extend($provider, $driver);

    return $driver;
}

beforeEach(function () {
    foreach (array_keys(config('larawebkit-ai.providers')) as $p) {
        config(["larawebkit-ai.providers.$p.api_key" => null]);
    }
});

it('is disabled and hidden without any provider key', function () {
    expect(app(AiManager::class)->enabled())->toBeFalse()
        ->and(app(AiManager::class)->available('tags'))->toBeFalse();

    $this->actingAs(editor())->get(PostResource::getUrl('create'))->assertOk()->assertDontSee(__('larawebkit-ai.assistant.generate_draft'));
    $this->postJson(route('larawebkit.ai.run', 'tags'), ['title' => 'x'])->assertUnprocessable();
});

it('uses the cheap model for tags and logs usage with estimated cost', function () {
    $driver = fakeAi();
    $user = editor();

    $out = app(AiManager::class)->run('tags', ['title' => 'Coffee', 'text' => 'About beans'], $user);

    expect($out)->toBe('ok')->and($driver->calls[0]['model'])->toBe('claude-haiku-5-5')
        ->and($driver->calls[0]['prompt'])->toContain('Coffee');
    $usage = AiUsage::sole();
    expect($usage->provider)->toBe('anthropic')
        ->and($usage->input_tokens)->toBe(1000)
        ->and((float) $usage->cost_usd)->toBe(AiManager::cost('claude-haiku-5-5', 1000, 500))
        ->and($usage->user_id)->toBe($user->id);
});

it('uses the strong model for drafts', function () {
    $driver = fakeAi();
    app(AiManager::class)->run('draft', ['title' => 'T', 'brief' => '', 'keywords' => '']);

    expect($driver->calls[0]['model'])->toBe('claude-opus-5-5')->and($driver->calls[0]['maxTokens'])->toBe(8000);
});

it('falls back to the next provider when one fails', function () {
    fakeAi('anthropic', new RuntimeException('down'));
    $openai = fakeAi('openai', 'from openai');

    expect(app(AiManager::class)->run('tags', ['title' => 'x']))->toBe('from openai')
        ->and(AiUsage::pluck('succeeded', 'provider')->all())->toBe(['anthropic' => false, 'openai' => true])
        ->and($openai->calls)->toHaveCount(1);
});

it('stops AI actions when the monthly budget is reached', function () {
    fakeAi();
    app(SettingsRepository::class)->set('ai.monthly_budget_usd', 1);
    AiUsage::create(['action' => 'draft', 'provider' => 'anthropic', 'model' => 'm', 'cost_usd' => 1.5, 'created_at' => now()]);

    expect(app(AiManager::class)->overBudget())->toBeTrue()->and(app(AiManager::class)->available('tags'))->toBeFalse();
    app(AiManager::class)->run('tags', ['title' => 'x']);
})->throws(RuntimeException::class);

it('renders locale-aware prompt files with placeholders', function () {
    $tr = app(PromptRepository::class)->render('draft', ['title' => 'Kahve'], 'tr');
    $en = app(PromptRepository::class)->render('draft', ['title' => 'Coffee'], 'en');

    expect($tr['system'])->toContain('Türkçe')->and($tr['user'])->toContain('Kahve')
        ->and($en['system'])->toContain('Write in English')->and($en['version'])->toBe('1');
});

it('runs short actions synchronously and queues drafts with status polling', function () {
    fakeAi('anthropic', '<p>Draft body</p>');
    $this->actingAs($user = editor());

    $this->postJson(route('larawebkit.ai.run', 'meta_description'), ['title' => 'T', 'text' => '<p>Body</p>'])
        ->assertOk()->assertJsonPath('output', '<p>Draft body</p>');

    $response = $this->postJson(route('larawebkit.ai.run', 'draft'), ['title' => 'T', 'keywords' => ['a', 'b']])->assertStatus(202);
    $task = AiTask::where('uuid', $response->json('task'))->sole();
    expect($task->status)->toBe('done'); // sync queue in tests

    $this->getJson(route('larawebkit.ai.task', $task->uuid))->assertOk()->assertJsonPath('output', '<p>Draft body</p>');
    $this->actingAs(editor())->getJson(route('larawebkit.ai.task', $task->uuid))->assertNotFound();
});

it('requires the use ai permission', function () {
    fakeAi();
    $role = Role::findOrCreate('viewer', 'web');
    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)->postJson(route('larawebkit.ai.run', 'tags'))->assertForbidden();
});

it('shows the assistant in the editor when configured', function () {
    fakeAi();
    $this->actingAs(editor())->get(PostResource::getUrl('create'))->assertSee(__('larawebkit-ai.assistant.generate_draft'));
});

<?php

use App\Modules\Media\ImagePipeline;
use App\Modules\Media\Models\Media;
use Illuminate\Http\UploadedFile;

it('lists media newest first with search and pagination', function () {
    $this->actingAs(editor());
    $pipeline = app(ImagePipeline::class);
    $old = $pipeline->store(fakeJpeg(400, 300), 'old-cat.jpg');
    $new = $pipeline->store(fakeJpeg(400, 300), 'new-dog.jpg');

    $this->getJson(route('larawebkit.media.index'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $new->id)
        ->assertJsonPath('data.1.id', $old->id)
        ->assertJsonPath('has_more', false);

    $this->getJson(route('larawebkit.media.index', ['search' => 'cat']))->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $old->id);
});

it('uploads, validates type and returns details', function () {
    $this->actingAs(author());
    $file = UploadedFile::fake()->createWithContent('upload.jpg', fakeJpeg(800, 600));

    $this->postJson(route('larawebkit.media.store'), ['file' => $file, 'alt' => 'Alt'])
        ->assertCreated()
        ->assertJsonPath('data.alt', 'Alt')
        ->assertJsonPath('data.width', 800)
        ->assertJsonPath('data.format', 'webp');

    $this->postJson(route('larawebkit.media.store'), ['file' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')])
        ->assertUnprocessable();
});

it('updates alt text, title and caption', function () {
    $this->actingAs(editor());
    $media = app(ImagePipeline::class)->store(fakeJpeg(400, 300), 'a.jpg');

    $this->patchJson(route('larawebkit.media.update', $media), ['alt' => 'New alt', 'caption' => 'Cap'])
        ->assertOk()->assertJsonPath('data.alt', 'New alt');
});

it('only deletes with permission and warns when in use', function () {
    $media = app(ImagePipeline::class)->store(fakeJpeg(400, 300), 'a.jpg');

    $this->actingAs(author())->deleteJson(route('larawebkit.media.destroy', $media))->assertForbidden();

    $media->usages()->create(['usable_type' => 'post', 'usable_id' => 1, 'context' => 'content']);
    $this->actingAs(editor())->deleteJson(route('larawebkit.media.destroy', $media))->assertStatus(409)->assertJsonPath('in_use', 1);
    $this->deleteJson(route('larawebkit.media.destroy', $media), ['force' => true])->assertOk();

    expect(Media::find($media->id))->toBeNull();
});

it('requires an admin', function () {
    $this->getJson(route('larawebkit.media.index'))->assertUnauthorized();
});

<?php

use App\Actions\Tags\CreateTag;
use App\Data\Tags\TagData;
use App\Livewire\Todos\Index;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Step 4 — Tags (owner-scoped labels)
|--------------------------------------------------------------------------
*/

it('lets a user create a tag in their own workspace', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(Index::class)
        ->set('newTagName', 'Errands')
        ->call('createTag')
        ->assertHasNoErrors();

    // Tag names are normalized to lower-case.
    expect($user->tags()->where('name', 'errands')->exists())->toBeTrue();
});

it('does not duplicate a tag with the same normalized name', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(Index::class)->set('newTagName', 'Work')->call('createTag');
    Livewire::actingAs($user)->test(Index::class)->set('newTagName', ' work ')->call('createTag');

    expect($user->tags()->where('name', 'work')->count())->toBe(1);
});

it('rejects a tag name that becomes empty after normalization', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(Index::class)
        ->set('newTagName', '   ')
        ->call('createTag')
        ->assertHasErrors(['newTagName']);

    expect($user->tags()->count())->toBe(0);
});

it('refuses direct tag creation with an empty normalized name', function () {
    $user = User::factory()->create();

    expect(fn () => app(CreateTag::class)->handle($user, TagData::fromArray(['name' => '   '])))
        ->toThrow(ValidationException::class);

    expect($user->tags()->count())->toBe(0);
});

it('lets two users own a tag with the same name independently', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();

    Livewire::actingAs($a)->test(Index::class)->set('newTagName', 'shared')->call('createTag');
    Livewire::actingAs($b)->test(Index::class)->set('newTagName', 'shared')->call('createTag');

    expect($a->tags()->where('name', 'shared')->count())->toBe(1)
        ->and($b->tags()->where('name', 'shared')->count())->toBe(1);
});

it('does not show another users tags', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    Tag::factory()->for($owner)->create(['name' => 'mine']);
    Tag::factory()->for($other)->create(['name' => 'theirs']);

    Livewire::actingAs($owner)->test(Index::class)
        ->assertSee('mine')
        ->assertDontSee('theirs');
});

it('links rendered tag badges to the current users tag filter', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $tag = Tag::factory()->for($owner)->create(['name' => 'review']);
    $foreignTag = Tag::factory()->for($other)->create(['name' => 'hidden']);
    $project = Project::factory()->for($owner)->create();
    $todo = Todo::factory()
        ->forProject($project)
        ->withTags($tag)
        ->create(['title' => 'Tagged owner task']);
    Todo::factory()->for($other)->withTags($foreignTag)->create(['title' => 'Tagged foreign task']);

    $this->actingAs($owner)
        ->get(route('todos.index'))
        ->assertOk()
        ->assertSee(route('todos.index', ['tag' => $tag->id]), false)
        ->assertDontSee(route('todos.index', ['tag' => $foreignTag->id]), false);

    $this->actingAs($owner)
        ->get(route('todos.show', $todo))
        ->assertOk()
        ->assertSee(route('todos.index', ['tag' => $tag->id]), false)
        ->assertDontSee(route('todos.index', ['tag' => $foreignTag->id]), false);

    $this->actingAs($owner)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSee(route('todos.index', ['tag' => $tag->id]), false)
        ->assertDontSee(route('todos.index', ['tag' => $foreignTag->id]), false);
});

it('forbids deleting another users tag', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $tag = Tag::factory()->for($owner)->create();

    expect(fn () => Livewire::actingAs($intruder)->test(Index::class)->call('deleteTag', $tag->id))
        ->toThrow(ModelNotFoundException::class);

    expect($tag->fresh())->not->toBeNull();
});

it('removes a tag from tasks when the tag is deleted, keeping the tasks', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create();
    $todo = Todo::factory()->for($user)->create();
    $todo->tags()->attach($tag);

    Livewire::actingAs($user)->test(Index::class)->call('deleteTag', $tag->id);

    expect(Tag::query()->find($tag->id))->toBeNull()
        ->and($todo->fresh())->not->toBeNull()
        ->and($todo->fresh()->tags)->toHaveCount(0);
});

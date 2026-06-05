<?php

use App\Livewire\Todos\Index;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

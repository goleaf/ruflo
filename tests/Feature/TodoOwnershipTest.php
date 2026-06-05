<?php

use App\Models\Todo;
use App\Models\User;
use App\Policies\TodoPolicy;
use App\Queries\Todos\TodoListQuery;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

/*
|--------------------------------------------------------------------------
| Step 2 — Private workspace, ownership, and authorization
|--------------------------------------------------------------------------
|
| These tests assert the access-control foundation at the backend boundary:
| the policy, the ownership scope, and the owner-scoped query. They must hold
| regardless of what the UI shows or hides.
|
*/

it('resolves the dedicated policy for the todo model', function () {
    expect(Gate::getPolicyFor(Todo::class))->toBeInstanceOf(TodoPolicy::class);
});

it('allows the owner every per-record ability', function () {
    $owner = User::factory()->create();
    $todo = Todo::factory()->for($owner)->create();

    $gate = Gate::forUser($owner);

    expect($gate->allows('view', $todo))->toBeTrue()
        ->and($gate->allows('update', $todo))->toBeTrue()
        ->and($gate->allows('complete', $todo))->toBeTrue()
        ->and($gate->allows('delete', $todo))->toBeTrue()
        ->and($gate->allows('restore', $todo))->toBeTrue();
});

it('denies every per-record ability to a non-owner', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $todo = Todo::factory()->for($owner)->create();

    $gate = Gate::forUser($intruder);

    expect($gate->denies('view', $todo))->toBeTrue()
        ->and($gate->denies('update', $todo))->toBeTrue()
        ->and($gate->denies('complete', $todo))->toBeTrue()
        ->and($gate->denies('delete', $todo))->toBeTrue()
        ->and($gate->denies('restore', $todo))->toBeTrue();
});

it('denies cross-user access as not found so existence does not leak', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $todo = Todo::factory()->for($owner)->create();

    $response = Gate::forUser($intruder)->inspect('view', $todo);

    expect($response->denied())->toBeTrue()
        ->and($response->status())->toBe(404);
});

it('never permanently deletes through the policy', function () {
    $owner = User::factory()->create();
    $todo = Todo::factory()->for($owner)->create();

    expect(Gate::forUser($owner)->denies('forceDelete', $todo))->toBeTrue();
});

it('refuses to mass-assign ownership', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $todo = new Todo;
    $todo->fill([
        'title' => 'Attempted hijack',
        'user_id' => $intruder->id,
    ]);
    $todo->user()->associate($owner)->save();

    expect($todo->fresh()->user_id)->toBe($owner->id);
});

it('scopes the visible query to the owner only', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    Todo::factory()->for($owner)->create(['title' => 'Mine']);
    Todo::factory()->for($other)->create(['title' => 'Theirs']);

    $titles = app(TodoListQuery::class)->visibleFor($owner)->pluck('title');

    expect($titles)->toContain('Mine')
        ->and($titles)->not->toContain('Theirs');
});

it('treats another users id as not found', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $foreignTodo = Todo::factory()->for($other)->create();

    expect(fn () => app(TodoListQuery::class)->findVisibleFor($owner, $foreignTodo->id))
        ->toThrow(ModelNotFoundException::class);
});

it('scopes ownedBy to a single workspace', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    Todo::factory()->for($owner)->count(2)->create();
    Todo::factory()->for($other)->count(3)->create();

    expect(Todo::query()->ownedBy($owner)->count())->toBe(2)
        ->and(Todo::query()->ownedBy($other)->count())->toBe(3);
});

it('answers ownership questions through the model helper', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $todo = Todo::factory()->for($owner)->create();

    expect($todo->isOwnedBy($owner))->toBeTrue()
        ->and($todo->isOwnedBy($other))->toBeFalse();
});

it('lets any authenticated user act inside their own workspace', function () {
    $user = User::factory()->create();
    $gate = Gate::forUser($user);

    expect($gate->allows('viewAny', Todo::class))->toBeTrue()
        ->and($gate->allows('create', Todo::class))->toBeTrue()
        ->and($gate->allows('clearCompleted', Todo::class))->toBeTrue();
});

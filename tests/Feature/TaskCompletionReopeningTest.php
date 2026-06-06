<?php

use App\Actions\Todos\BulkCompleteTodos;
use App\Actions\Todos\CompleteTodo;
use App\Actions\Todos\ReopenTodo;
use App\Enums\TodoStatus;
use App\Events\TodoCompleted;
use App\Events\TodoReopened;
use App\Exceptions\InvalidTodoTransition;
use App\Livewire\Todos\Index;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Todos\TodoListQuery;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

test('complete action completes an active task and dispatches a specific event', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();

    Event::fake([TodoCompleted::class, TodoReopened::class]);

    app(CompleteTodo::class)->handle($todo);

    expect($todo->refresh()->is_completed)->toBeTrue()
        ->and($todo->status())->toBe(TodoStatus::Completed)
        ->and($todo->archived_at)->toBeNull()
        ->and($todo->trashed())->toBeFalse();

    Event::assertDispatched(TodoCompleted::class, fn (TodoCompleted $event): bool => $event->todo->is($todo));
    Event::assertNotDispatched(TodoReopened::class);
});

test('reopen action returns a completed task to active and dispatches a specific event', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->completed()->create();

    Event::fake([TodoCompleted::class, TodoReopened::class]);

    app(ReopenTodo::class)->handle($todo);

    expect($todo->refresh()->is_completed)->toBeFalse()
        ->and($todo->status())->toBe(TodoStatus::Active)
        ->and($todo->archived_at)->toBeNull()
        ->and($todo->trashed())->toBeFalse();

    Event::assertDispatched(TodoReopened::class, fn (TodoReopened $event): bool => $event->todo->is($todo));
    Event::assertNotDispatched(TodoCompleted::class);
});

test('completion transitions are idempotent without duplicate events', function () {
    $user = User::factory()->create();
    $completed = Todo::factory()->for($user)->completed()->create();
    $active = Todo::factory()->for($user)->create();

    Event::fake([TodoCompleted::class, TodoReopened::class]);

    app(CompleteTodo::class)->handle($completed);
    app(ReopenTodo::class)->handle($active);

    expect($completed->refresh()->is_completed)->toBeTrue()
        ->and($active->refresh()->is_completed)->toBeFalse();

    Event::assertNotDispatched(TodoCompleted::class);
    Event::assertNotDispatched(TodoReopened::class);
});

test('archived tasks cannot be completed or reopened through the action layer', function () {
    $user = User::factory()->create();
    $archivedActive = Todo::factory()->for($user)->archived()->create();
    $archivedCompleted = Todo::factory()->for($user)->completed()->archived()->create();

    expect(fn () => app(CompleteTodo::class)->handle($archivedActive))
        ->toThrow(InvalidTodoTransition::class);

    expect(fn () => app(ReopenTodo::class)->handle($archivedCompleted))
        ->toThrow(InvalidTodoTransition::class);

    expect($archivedActive->refresh()->is_completed)->toBeFalse()
        ->and($archivedActive->isArchived())->toBeTrue()
        ->and($archivedCompleted->refresh()->is_completed)->toBeTrue()
        ->and($archivedCompleted->isArchived())->toBeTrue();
});

test('livewire completes and reopens owned tasks with scoped dashboard counts', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['title' => 'Finish this']);

    Event::fake([TodoCompleted::class, TodoReopened::class]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSet('summary.active', 1)
        ->assertSet('summary.completed', 0)
        ->call('completeTodo', $todo->id)
        ->assertSet('summary.active', 0)
        ->assertSet('summary.completed', 1)
        ->call('reopenTodo', $todo->id)
        ->assertSet('summary.active', 1)
        ->assertSet('summary.completed', 0);

    expect($todo->refresh()->is_completed)->toBeFalse();

    Event::assertDispatched(TodoCompleted::class, fn (TodoCompleted $event): bool => $event->todo->is($todo));
    Event::assertDispatched(TodoReopened::class, fn (TodoReopened $event): bool => $event->todo->is($todo));
});

test('foreign task ids cannot complete or reopen private tasks', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $active = Todo::factory()->for($owner)->create();
    $completed = Todo::factory()->for($owner)->completed()->create();

    expect(fn () => Livewire::actingAs($intruder)
        ->test(Index::class)
        ->call('completeTodo', $active->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => Livewire::actingAs($intruder)
        ->test(Index::class)
        ->call('reopenTodo', $completed->id))
        ->toThrow(ModelNotFoundException::class);

    expect($active->refresh()->is_completed)->toBeFalse()
        ->and($completed->refresh()->is_completed)->toBeTrue();
});

test('bulk complete reuses the complete transition and ignores non-actionable ids', function () {
    $user = User::factory()->create();
    $active = Todo::factory()->for($user)->create();
    $completed = Todo::factory()->for($user)->completed()->create();
    $archived = Todo::factory()->for($user)->archived()->create();
    $foreign = Todo::factory()->create();

    Event::fake([TodoCompleted::class]);

    $result = app(BulkCompleteTodos::class)->handle($user, [
        $active->id,
        $completed->id,
        $archived->id,
        $foreign->id,
    ]);

    expect($result->affected)->toBe(1)
        ->and($result->selected)->toBe(4)
        ->and($result->skipped)->toBe(3)
        ->and($result->failed)->toBe(0)
        ->and($active->refresh()->is_completed)->toBeTrue()
        ->and($completed->refresh()->is_completed)->toBeTrue()
        ->and($archived->refresh()->is_completed)->toBeFalse()
        ->and($foreign->refresh()->is_completed)->toBeFalse();

    Event::assertDispatchedTimes(TodoCompleted::class, 1);
});

test('completion UI uses explicit complete and reopen actions and labels', function () {
    $source = file_get_contents(resource_path('views/livewire/todos/index.blade.php'));

    expect($source)
        ->toContain('completeTodo')
        ->toContain('reopenTodo')
        ->toContain('todos.actions.complete')
        ->toContain('todos.actions.reopen')
        ->not->toContain('toggleTodo')
        ->not->toContain('todos.actions.toggle');
});

test('todo detail lookups reflect the updated completion state', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();
    $query = app(TodoListQuery::class);

    app(CompleteTodo::class)->handle($todo);

    expect($query->findVisibleFor($user, $todo->id)->status())->toBe(TodoStatus::Completed);

    app(ReopenTodo::class)->handle($todo);

    expect($query->findVisibleFor($user, $todo->id)->status())->toBe(TodoStatus::Active);
});

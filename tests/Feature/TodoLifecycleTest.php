<?php

use App\Enums\TodoStatus;
use App\Events\TodoArchived;
use App\Events\TodoCompleted;
use App\Events\TodoReopened;
use App\Events\TodoUnarchived;
use App\Events\TodoUpdated;
use App\Livewire\Todos\Index;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Step 3 — Core task lifecycle
|--------------------------------------------------------------------------
|
| active ⇄ completed, active/completed → archived → (back to prior bucket),
| any non-deleted → trashed (soft delete). Every transition is owner-scoped
| and authorized; invalid transitions are rejected safely.
|
*/

it('derives the display status from the underlying columns', function () {
    $active = Todo::factory()->make();
    $completed = Todo::factory()->completed()->make();
    $archived = Todo::factory()->archived()->make();
    $archivedCompleted = Todo::factory()->completed()->archived()->make();

    expect($active->status())->toBe(TodoStatus::Active)
        ->and($completed->status())->toBe(TodoStatus::Completed)
        ->and($archived->status())->toBe(TodoStatus::Archived)
        ->and($archivedCompleted->status())->toBe(TodoStatus::Archived);
});

it('shows only the selected lifecycle bucket', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->create(['title' => 'Active task']);
    Todo::factory()->for($user)->completed()->create(['title' => 'Completed task']);
    Todo::factory()->for($user)->archived()->create(['title' => 'Archived task']);

    Livewire::actingAs($user)->test(Index::class)
        ->assertSet('tab', 'active')
        ->assertSee('Active task')
        ->assertDontSee('Completed task')
        ->assertDontSee('Archived task')
        ->set('tab', 'completed')
        ->assertSee('Completed task')
        ->assertDontSee('Active task')
        ->set('tab', 'archived')
        ->assertSee('Archived task')
        ->assertDontSee('Active task');
});

it('reports an accurate per-bucket summary', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->count(2)->create();
    Todo::factory()->for($user)->completed()->create();
    Todo::factory()->for($user)->archived()->count(3)->create();

    Livewire::actingAs($user)->test(Index::class)
        ->assertSet('summary.active', 2)
        ->assertSet('summary.completed', 1)
        ->assertSet('summary.archived', 3);
});

it('archives an active task without deleting or completing it', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();

    Event::fake([TodoArchived::class]);

    Livewire::actingAs($user)->test(Index::class)
        ->call('archiveTodo', $todo->id);

    $todo->refresh();

    expect($todo->isArchived())->toBeTrue()
        ->and($todo->is_completed)->toBeFalse()
        ->and($todo->trashed())->toBeFalse();

    Event::assertDispatched(TodoArchived::class);
});

it('unarchives an archived task back to its prior completion state', function () {
    $user = User::factory()->create();
    $wasActive = Todo::factory()->for($user)->archived()->create();
    $wasCompleted = Todo::factory()->for($user)->completed()->archived()->create();

    Event::fake([TodoUnarchived::class]);

    Livewire::actingAs($user)->test(Index::class)
        ->call('unarchiveTodo', $wasActive->id)
        ->call('unarchiveTodo', $wasCompleted->id);

    expect($wasActive->refresh()->status())->toBe(TodoStatus::Active)
        ->and($wasCompleted->refresh()->status())->toBe(TodoStatus::Completed);

    Event::assertDispatchedTimes(TodoUnarchived::class, 2);
});

it('refuses to complete or reopen an archived task', function () {
    $user = User::factory()->create();
    $activeBeforeArchive = Todo::factory()->for($user)->archived()->create();
    $completedBeforeArchive = Todo::factory()->for($user)->completed()->archived()->create();

    Livewire::actingAs($user)->test(Index::class)
        ->call('completeTodo', $activeBeforeArchive->id)
        ->call('reopenTodo', $completedBeforeArchive->id);

    expect($activeBeforeArchive->refresh()->is_completed)->toBeFalse()
        ->and($activeBeforeArchive->isArchived())->toBeTrue()
        ->and($completedBeforeArchive->refresh()->is_completed)->toBeTrue()
        ->and($completedBeforeArchive->isArchived())->toBeTrue();
});

it('completes and reopens tasks as separate transitions', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['title' => 'Lifecycle task']);

    Event::fake([TodoCompleted::class, TodoReopened::class]);

    Livewire::actingAs($user)->test(Index::class)
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

it('edits an own active task title', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['title' => 'Original']);

    Event::fake([TodoUpdated::class]);

    Livewire::actingAs($user)->test(Index::class)
        ->call('startEdit', $todo->id)
        ->assertSet('showEditModal', true)
        ->assertSet('editForm.title', 'Original')
        ->set('editForm.title', 'Revised title')
        ->call('saveEdit')
        ->assertSet('showEditModal', false);

    expect($todo->refresh()->title)->toBe('Revised title');
    Event::assertDispatched(TodoUpdated::class);
});

it('rejects an empty title on edit', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['title' => 'Keep me']);

    Livewire::actingAs($user)->test(Index::class)
        ->call('startEdit', $todo->id)
        ->set('editForm.title', '')
        ->call('saveEdit')
        ->assertHasErrors(['editForm.title' => 'required']);

    expect($todo->refresh()->title)->toBe('Keep me');
});

it('does not open the edit modal for an archived task', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->archived()->create();

    Livewire::actingAs($user)->test(Index::class)
        ->call('startEdit', $todo->id)
        ->assertSet('showEditModal', false);
});

it('soft deletes a task so it can be recovered by design', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();

    Livewire::actingAs($user)->test(Index::class)
        ->call('deleteTodo', $todo->id);

    expect(Todo::query()->find($todo->id))->toBeNull()
        ->and(Todo::withTrashed()->find($todo->id))->not->toBeNull();
});

it('clears completed tasks without touching archived ones', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->completed()->create(['title' => 'Done']);
    $archivedCompleted = Todo::factory()->for($user)->completed()->archived()->create();
    Todo::factory()->for($user)->create(['title' => 'Active']);

    Livewire::actingAs($user)->test(Index::class)
        ->set('tab', 'completed')
        ->call('clearCompleted');

    expect($user->todos()->pluck('title'))->toContain('Active')
        ->and($user->todos()->pluck('title'))->not->toContain('Done')
        ->and($archivedCompleted->refresh()->trashed())->toBeFalse();
});

it('falls back to the active tab for an invalid tab value', function () {
    $user = User::factory()->create();

    Livewire::withQueryParams(['tab' => 'definitely-not-a-tab'])
        ->actingAs($user)
        ->test(Index::class)
        ->assertSet('tab', 'active');
});

/*
|--------------------------------------------------------------------------
| Cross-user isolation across every lifecycle action
|--------------------------------------------------------------------------
*/

it('forbids acting on another users task for every lifecycle action', function (string $method) {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $todo = Todo::factory()->for($owner)->create();

    expect(fn () => Livewire::actingAs($intruder)
        ->test(Index::class)
        ->call($method, $todo->id))
        ->toThrow(ModelNotFoundException::class);
})->with([
    'completeTodo',
    'reopenTodo',
    'startEdit',
    'archiveTodo',
    'unarchiveTodo',
    'deleteTodo',
]);

it('does not let an intruder change another users task', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $todo = Todo::factory()->for($owner)->create(['title' => 'Owners task', 'is_completed' => false]);

    try {
        Livewire::actingAs($intruder)->test(Index::class)->call('archiveTodo', $todo->id);
    } catch (ModelNotFoundException) {
        // expected — the owner-scoped lookup hides the record entirely
    }

    $todo->refresh();
    expect($todo->isArchived())->toBeFalse()
        ->and($todo->title)->toBe('Owners task');
});

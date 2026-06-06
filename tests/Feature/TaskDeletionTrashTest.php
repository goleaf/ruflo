<?php

use App\Actions\Todos\BulkDeleteTodos;
use App\Actions\Todos\BulkRestoreDeletedTodos;
use App\Actions\Todos\DeleteTodo;
use App\Actions\Todos\RestoreDeletedTodo;
use App\Enums\TodoStatus;
use App\Events\TodoDeleted;
use App\Events\TodoRestoredFromTrash;
use App\Livewire\Todos\Index;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Todos\TodoListQuery;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

test('delete action soft deletes once and dispatches the deleted event once', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();

    Event::fake([TodoDeleted::class]);

    app(DeleteTodo::class)->handle($todo);
    app(DeleteTodo::class)->handle($todo);

    expect(Todo::query()->find($todo->id))->toBeNull()
        ->and(Todo::withTrashed()->find($todo->id))->not->toBeNull()
        ->and(Todo::withTrashed()->find($todo->id)->status())->toBe(TodoStatus::Trash);

    Event::assertDispatchedTimes(TodoDeleted::class, 1);
});

test('trash tab shows only owned deleted tasks and hides active details', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    Todo::factory()->for($owner)->create(['title' => 'Still active']);
    Todo::factory()->for($owner)->deleted()->create(['title' => 'Mine in trash']);
    Todo::factory()->for($intruder)->deleted()->create(['title' => 'Foreign trash']);

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->set('tab', TodoStatus::Trash->value)
        ->assertSet('summary.trash', 1)
        ->assertSee('Mine in trash')
        ->assertDontSee('Still active')
        ->assertDontSee('Foreign trash')
        ->assertSee(__('todos.actions.restore_from_trash'));
});

test('restore deleted action preserves archive and completion state', function () {
    $user = User::factory()->create();
    $activeBeforeDelete = Todo::factory()->for($user)->deleted()->create();
    $completedArchivedBeforeDelete = Todo::factory()
        ->for($user)
        ->completed()
        ->archived()
        ->deleted()
        ->create();

    Event::fake([TodoRestoredFromTrash::class]);

    app(RestoreDeletedTodo::class)->handle($activeBeforeDelete);
    app(RestoreDeletedTodo::class)->handle($completedArchivedBeforeDelete);

    expect($activeBeforeDelete->refresh()->status())->toBe(TodoStatus::Active)
        ->and($completedArchivedBeforeDelete->refresh()->status())->toBe(TodoStatus::Archived)
        ->and($completedArchivedBeforeDelete->is_completed)->toBeTrue();

    Event::assertDispatchedTimes(TodoRestoredFromTrash::class, 2);
});

test('livewire restores owned deleted tasks and refuses foreign or visible ids', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $deleted = Todo::factory()->for($owner)->deleted()->create();
    $visible = Todo::factory()->for($owner)->create();
    $foreignDeleted = Todo::factory()->for($intruder)->deleted()->create();

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->set('tab', TodoStatus::Trash->value)
        ->assertSet('summary.trash', 1)
        ->call('restoreDeletedTodo', $deleted->id)
        ->assertSet('summary.trash', 0)
        ->assertSet('summary.active', 2);

    expect($deleted->refresh()->trashed())->toBeFalse();

    expect(fn () => Livewire::actingAs($owner)
        ->test(Index::class)
        ->call('restoreDeletedTodo', $visible->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => Livewire::actingAs($owner)
        ->test(Index::class)
        ->call('restoreDeletedTodo', $foreignDeleted->id))
        ->toThrow(ModelNotFoundException::class);
});

test('bulk delete and restore reuse eventful single task transitions', function () {
    $user = User::factory()->create();
    $first = Todo::factory()->for($user)->create();
    $second = Todo::factory()->for($user)->completed()->create();
    $foreign = Todo::factory()->create();

    Event::fake([TodoDeleted::class, TodoRestoredFromTrash::class]);

    $deletedCount = app(BulkDeleteTodos::class)->handle($user, [
        $first->id,
        $second->id,
        $foreign->id,
    ]);

    expect($deletedCount)->toBe(2)
        ->and($first->refresh()->trashed())->toBeTrue()
        ->and($second->refresh()->trashed())->toBeTrue()
        ->and($foreign->refresh()->trashed())->toBeFalse();

    $restoredCount = app(BulkRestoreDeletedTodos::class)->handle($user, [
        $first->id,
        $second->id,
        $foreign->id,
    ]);

    expect($restoredCount)->toBe(2)
        ->and($first->refresh()->trashed())->toBeFalse()
        ->and($second->refresh()->status())->toBe(TodoStatus::Completed);

    Event::assertDispatchedTimes(TodoDeleted::class, 2);
    Event::assertDispatchedTimes(TodoRestoredFromTrash::class, 2);
});

test('trash bulk selection validates deleted ownership and restores only deleted tasks', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $ownDeleted = Todo::factory()->for($owner)->deleted()->create();
    $ownVisible = Todo::factory()->for($owner)->create();
    $foreignDeleted = Todo::factory()->for($intruder)->deleted()->create();

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->set('tab', TodoStatus::Trash->value)
        ->set('selected', [$ownDeleted->id, $ownVisible->id])
        ->call('bulkRestoreDeleted')
        ->assertHasErrors(['selected.1']);

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->set('tab', TodoStatus::Trash->value)
        ->set('selected', [$ownDeleted->id, $foreignDeleted->id])
        ->call('bulkRestoreDeleted')
        ->assertHasErrors(['selected.1']);

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->set('tab', TodoStatus::Trash->value)
        ->set('selected', [$ownDeleted->id])
        ->call('bulkRestoreDeleted')
        ->assertHasNoErrors();

    expect($ownDeleted->refresh()->trashed())->toBeFalse()
        ->and($ownVisible->refresh()->trashed())->toBeFalse()
        ->and($foreignDeleted->refresh()->trashed())->toBeTrue();
});

test('permanent delete remains disabled and absent from the task UI', function () {
    $user = User::factory()->create();
    $deleted = Todo::factory()->for($user)->deleted()->create();
    $source = file_get_contents(resource_path('views/livewire/todos/index.blade.php'));

    expect(Gate::forUser($user)->denies('forceDelete', $deleted))->toBeTrue()
        ->and($source)->not->toContain('forceDelete')
        ->and($source)->not->toContain('permanent');
});

test('owner scoped query resolves trash separately from visible tasks', function () {
    $user = User::factory()->create();
    $visible = Todo::factory()->for($user)->create();
    $deleted = Todo::factory()->for($user)->deleted()->create();
    $query = app(TodoListQuery::class);

    expect($query->findTrashedFor($user, $deleted->id)->status())->toBe(TodoStatus::Trash);

    expect(fn () => $query->findVisibleFor($user, $deleted->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => $query->findTrashedFor($user, $visible->id))
        ->toThrow(ModelNotFoundException::class);
});

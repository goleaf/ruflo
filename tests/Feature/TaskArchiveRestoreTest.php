<?php

use App\Actions\Todos\ArchiveTodo;
use App\Actions\Todos\BulkArchiveTodos;
use App\Actions\Todos\BulkUnarchiveTodos;
use App\Actions\Todos\UnarchiveTodo;
use App\Enums\TodoStatus;
use App\Events\TodoArchived;
use App\Events\TodoUnarchived;
use App\Livewire\Todos\Index;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Todos\TodoListQuery;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

test('archive action archives without completing or deleting the task', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();

    Event::fake([TodoArchived::class, TodoUnarchived::class]);

    app(ArchiveTodo::class)->handle($todo);

    expect($todo->refresh()->isArchived())->toBeTrue()
        ->and($todo->is_completed)->toBeFalse()
        ->and($todo->trashed())->toBeFalse()
        ->and($todo->status())->toBe(TodoStatus::Archived);

    Event::assertDispatched(TodoArchived::class, fn (TodoArchived $event): bool => $event->todo->is($todo));
    Event::assertNotDispatched(TodoUnarchived::class);
});

test('unarchive action preserves the prior completion state', function () {
    $user = User::factory()->create();
    $activeBeforeArchive = Todo::factory()->for($user)->archived()->create();
    $completedBeforeArchive = Todo::factory()->for($user)->completed()->archived()->create();

    Event::fake([TodoArchived::class, TodoUnarchived::class]);

    app(UnarchiveTodo::class)->handle($activeBeforeArchive);
    app(UnarchiveTodo::class)->handle($completedBeforeArchive);

    expect($activeBeforeArchive->refresh()->status())->toBe(TodoStatus::Active)
        ->and($completedBeforeArchive->refresh()->status())->toBe(TodoStatus::Completed)
        ->and($completedBeforeArchive->is_completed)->toBeTrue()
        ->and($activeBeforeArchive->trashed())->toBeFalse()
        ->and($completedBeforeArchive->trashed())->toBeFalse();

    Event::assertDispatchedTimes(TodoUnarchived::class, 2);
    Event::assertNotDispatched(TodoArchived::class);
});

test('archive and unarchive actions are idempotent without duplicate events', function () {
    $user = User::factory()->create();
    $archived = Todo::factory()->for($user)->archived()->create();
    $active = Todo::factory()->for($user)->create();

    Event::fake([TodoArchived::class, TodoUnarchived::class]);

    app(ArchiveTodo::class)->handle($archived);
    app(UnarchiveTodo::class)->handle($active);

    expect($archived->refresh()->isArchived())->toBeTrue()
        ->and($active->refresh()->isArchived())->toBeFalse();

    Event::assertNotDispatched(TodoArchived::class);
    Event::assertNotDispatched(TodoUnarchived::class);
});

test('livewire archives and unarchives owned tasks with scoped summary counts', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->completed()->create(['title' => 'Archive me']);

    Event::fake([TodoArchived::class, TodoUnarchived::class]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSet('summary.completed', 1)
        ->assertSet('summary.archived', 0)
        ->call('archiveTodo', $todo->id)
        ->assertSet('summary.completed', 0)
        ->assertSet('summary.archived', 1)
        ->call('unarchiveTodo', $todo->id)
        ->assertSet('summary.completed', 1)
        ->assertSet('summary.archived', 0);

    expect($todo->refresh()->status())->toBe(TodoStatus::Completed);

    Event::assertDispatched(TodoArchived::class, fn (TodoArchived $event): bool => $event->todo->is($todo));
    Event::assertDispatched(TodoUnarchived::class, fn (TodoUnarchived $event): bool => $event->todo->is($todo));
});

test('foreign task ids cannot be archived or unarchived', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $active = Todo::factory()->for($owner)->create();
    $archived = Todo::factory()->for($owner)->archived()->create();

    expect(fn () => Livewire::actingAs($intruder)
        ->test(Index::class)
        ->call('archiveTodo', $active->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => Livewire::actingAs($intruder)
        ->test(Index::class)
        ->call('unarchiveTodo', $archived->id))
        ->toThrow(ModelNotFoundException::class);

    expect($active->refresh()->isArchived())->toBeFalse()
        ->and($archived->refresh()->isArchived())->toBeTrue();
});

test('bulk archive and unarchive reuse the eventful task transitions', function () {
    $user = User::factory()->create();
    $active = Todo::factory()->for($user)->create();
    $alreadyArchived = Todo::factory()->for($user)->archived()->create();
    $foreign = Todo::factory()->create();

    Event::fake([TodoArchived::class, TodoUnarchived::class]);

    $archivedResult = app(BulkArchiveTodos::class)->handle($user, [
        $active->id,
        $alreadyArchived->id,
        $foreign->id,
    ]);

    expect($archivedResult->affected)->toBe(1)
        ->and($archivedResult->selected)->toBe(3)
        ->and($archivedResult->skipped)->toBe(2)
        ->and($archivedResult->failed)->toBe(0)
        ->and($active->refresh()->isArchived())->toBeTrue()
        ->and($alreadyArchived->refresh()->isArchived())->toBeTrue()
        ->and($foreign->refresh()->isArchived())->toBeFalse();

    $unarchivedResult = app(BulkUnarchiveTodos::class)->handle($user, [
        $active->id,
        $alreadyArchived->id,
        $foreign->id,
    ]);

    expect($unarchivedResult->affected)->toBe(2)
        ->and($unarchivedResult->selected)->toBe(3)
        ->and($unarchivedResult->skipped)->toBe(1)
        ->and($unarchivedResult->failed)->toBe(0)
        ->and($active->refresh()->isArchived())->toBeFalse()
        ->and($alreadyArchived->refresh()->isArchived())->toBeFalse()
        ->and($foreign->refresh()->isArchived())->toBeFalse();

    Event::assertDispatchedTimes(TodoArchived::class, 1);
    Event::assertDispatchedTimes(TodoUnarchived::class, 2);
});

test('archive tab UI uses unarchive language and never delete language for archive return', function () {
    $source = file_get_contents(resource_path('views/livewire/todos/index.blade.php'));

    expect($source)
        ->toContain('archiveTodo')
        ->toContain('unarchiveTodo')
        ->toContain('bulkArchive')
        ->toContain('bulkUnarchive')
        ->toContain('todos.actions.unarchive')
        ->toContain('todos.bulk.unarchive')
        ->not->toContain('restoreTodo')
        ->not->toContain('wire:click="bulkRestore"');
});

test('owner scoped lookups reflect archive and unarchive status changes', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();
    $query = app(TodoListQuery::class);

    app(ArchiveTodo::class)->handle($todo);

    expect($query->findVisibleFor($user, $todo->id)->status())->toBe(TodoStatus::Archived);

    app(UnarchiveTodo::class)->handle($todo);

    expect($query->findVisibleFor($user, $todo->id)->status())->toBe(TodoStatus::Active);
});

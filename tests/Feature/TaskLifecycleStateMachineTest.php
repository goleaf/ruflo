<?php

use App\Actions\Todos\ArchiveTodo;
use App\Actions\Todos\CompleteTodo;
use App\Actions\Todos\DeleteTodo;
use App\Actions\Todos\RestoreDeletedTodo;
use App\Actions\Todos\TodoLifecycleStateMachine;
use App\Actions\Todos\UnarchiveTodo;
use App\Actions\Todos\UpdateTodo;
use App\Data\Todos\TodoData;
use App\Enums\TodoStatus;
use App\Enums\TodoTransition;
use App\Exceptions\InvalidTodoTransition;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\Lang;

test('state machine accepts only valid source states for each transition', function () {
    $machine = app(TodoLifecycleStateMachine::class);
    $active = Todo::factory()->make();
    $completed = Todo::factory()->completed()->make();
    $archived = Todo::factory()->archived()->make();
    $trash = Todo::factory()->deleted()->make();

    expect($machine->can($active, TodoTransition::Complete))->toBeTrue()
        ->and($machine->can($completed, TodoTransition::Complete))->toBeTrue()
        ->and($machine->can($archived, TodoTransition::Complete))->toBeFalse()
        ->and($machine->can($trash, TodoTransition::Complete))->toBeFalse()
        ->and($machine->can($completed, TodoTransition::Reopen))->toBeTrue()
        ->and($machine->can($trash, TodoTransition::Reopen))->toBeFalse()
        ->and($machine->can($archived, TodoTransition::Archive))->toBeTrue()
        ->and($machine->can($trash, TodoTransition::Archive))->toBeFalse()
        ->and($machine->can($active, TodoTransition::Unarchive))->toBeTrue()
        ->and($machine->can($trash, TodoTransition::Unarchive))->toBeFalse()
        ->and($machine->can($trash, TodoTransition::Delete))->toBeTrue()
        ->and($machine->can($active, TodoTransition::RestoreDeleted))->toBeTrue()
        ->and($machine->can($trash, TodoTransition::RestoreDeleted))->toBeTrue()
        ->and($machine->can($active, TodoTransition::Update))->toBeTrue()
        ->and($machine->can($archived, TodoTransition::Update))->toBeFalse()
        ->and($machine->can($trash, TodoTransition::Update))->toBeFalse();
});

test('state machine reports target buckets without mutating the task', function () {
    $machine = app(TodoLifecycleStateMachine::class);
    $active = Todo::factory()->make();
    $completed = Todo::factory()->completed()->make();
    $archivedCompleted = Todo::factory()->completed()->archived()->make();
    $trashedArchived = Todo::factory()->archived()->deleted()->make();

    expect($machine->targetStatus($active, TodoTransition::Complete))->toBe(TodoStatus::Completed)
        ->and($active->status())->toBe(TodoStatus::Active)
        ->and($machine->targetStatus($completed, TodoTransition::Reopen))->toBe(TodoStatus::Active)
        ->and($machine->targetStatus($active, TodoTransition::Archive))->toBe(TodoStatus::Archived)
        ->and($machine->targetStatus($archivedCompleted, TodoTransition::Unarchive))->toBe(TodoStatus::Completed)
        ->and($machine->targetStatus($active, TodoTransition::Delete))->toBe(TodoStatus::Trash)
        ->and($machine->targetStatus($trashedArchived, TodoTransition::RestoreDeleted))->toBe(TodoStatus::Archived);
});

test('actions reject direct invalid transitions against trashed tasks', function () {
    $user = User::factory()->create();
    $trashed = Todo::factory()->for($user)->deleted()->create();

    expect(fn () => app(CompleteTodo::class)->handle($trashed))
        ->toThrow(InvalidTodoTransition::class);

    expect(fn () => app(ArchiveTodo::class)->handle($trashed))
        ->toThrow(InvalidTodoTransition::class);

    expect(fn () => app(UnarchiveTodo::class)->handle($trashed))
        ->toThrow(InvalidTodoTransition::class);

    expect(fn () => app(UpdateTodo::class)->handle($user, $trashed, new TodoData(title: 'Edit deleted task')))
        ->toThrow(InvalidTodoTransition::class);

    expect($trashed->refresh()->trashed())->toBeTrue()
        ->and($trashed->is_completed)->toBeFalse()
        ->and($trashed->isArchived())->toBeFalse();
});

test('actions still allow idempotent delete and restore no ops', function () {
    $user = User::factory()->create();
    $active = Todo::factory()->for($user)->create();
    $trashed = Todo::factory()->for($user)->deleted()->create();

    app(RestoreDeletedTodo::class)->handle($active);
    app(DeleteTodo::class)->handle($trashed);

    expect($active->refresh()->status())->toBe(TodoStatus::Active)
        ->and($trashed->refresh()->status())->toBe(TodoStatus::Trash);
});

test('invalid transition exception messages are translated', function () {
    $keys = [
        'todos.exceptions.cannot_complete_archived',
        'todos.exceptions.cannot_reopen_archived',
        'todos.exceptions.cannot_edit_archived',
        'todos.exceptions.cannot_complete_trashed',
        'todos.exceptions.cannot_reopen_trashed',
        'todos.exceptions.cannot_archive_trashed',
        'todos.exceptions.cannot_unarchive_trashed',
        'todos.exceptions.cannot_edit_trashed',
        'todos.exceptions.invalid_transition',
    ];

    foreach ($keys as $key) {
        expect(Lang::has($key))->toBeTrue();
    }

    expect(InvalidTodoTransition::cannotCompleteArchived()->getMessage())
        ->toBe(__('todos.exceptions.cannot_complete_archived'))
        ->and(InvalidTodoTransition::cannotEditTrashed()->getMessage())
        ->toBe(__('todos.exceptions.cannot_edit_trashed'))
        ->and(InvalidTodoTransition::invalid(TodoStatus::Trash, TodoTransition::Archive)->getMessage())
        ->toBe(__('todos.exceptions.invalid_transition', [
            'status' => 'trash',
            'transition' => 'archive',
        ]));
});

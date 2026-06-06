<?php

use App\Actions\Todos\MoveTodoOnBoard;
use App\Enums\TodoStatus;
use App\Livewire\Todos\Board;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('board route is protected and renders only current user tasks', function () {
    $owner = User::factory()->create();
    $active = Todo::factory()->for($owner)->create(['title' => 'Owner active card']);
    $completed = Todo::factory()->for($owner)->completed()->create(['title' => 'Owner completed card']);
    $archived = Todo::factory()->for($owner)->archived()->create(['title' => 'Owner archived card']);
    $foreign = Todo::factory()->create(['title' => 'Foreign hidden card']);

    $this->get(route('todos.board'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('todos.board'))
        ->assertRedirect(route('verification.notice'));

    $this->actingAs($owner)
        ->get(route('todos.board'))
        ->assertOk()
        ->assertSee(__('todos.pages.board.title'))
        ->assertSee(__('todos.tabs.active'))
        ->assertSee(__('todos.tabs.completed'))
        ->assertSee(__('todos.tabs.archived'))
        ->assertSee('Owner active card')
        ->assertSee('Owner completed card')
        ->assertSee('Owner archived card')
        ->assertSee(route('todos.show', $active), false)
        ->assertDontSee('Foreign hidden card')
        ->assertDontSee(route('todos.show', $foreign), false);

    expect($active->exists)->toBeTrue()
        ->and($completed->exists)->toBeTrue()
        ->and($archived->exists)->toBeTrue();
});

test('users can move board cards between lifecycle columns with fallback actions', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();
    $archivedCompleted = Todo::factory()->for($user)->completed()->archived()->create();

    Livewire::actingAs($user)
        ->test(Board::class)
        ->call('moveToStatus', $todo->id, TodoStatus::Completed->value)
        ->assertHasNoErrors()
        ->call('moveToStatus', $todo->id, TodoStatus::Archived->value)
        ->assertHasNoErrors()
        ->call('moveToStatus', $todo->id, TodoStatus::Active->value)
        ->assertHasNoErrors()
        ->call('moveToStatus', $archivedCompleted->id, TodoStatus::Active->value)
        ->assertHasNoErrors();

    expect($todo->refresh()->status())->toBe(TodoStatus::Active)
        ->and($archivedCompleted->refresh()->status())->toBe(TodoStatus::Active);
});

test('board project movement accepts owned active projects and rejects unsafe targets', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $todo = Todo::factory()->for($owner)->create();
    $ownedProject = Project::factory()->for($owner)->create();
    $foreignProject = Project::factory()->for($other)->create();
    $archivedProject = Project::factory()->for($owner)->archived()->create();

    Livewire::actingAs($owner)
        ->test(Board::class)
        ->set("projectMoves.{$todo->id}", (string) $ownedProject->id)
        ->call('moveProject', $todo->id)
        ->assertHasNoErrors()
        ->set("projectMoves.{$todo->id}", (string) $foreignProject->id)
        ->call('moveProject', $todo->id)
        ->assertHasErrors(["projectMoves.{$todo->id}"])
        ->set("projectMoves.{$todo->id}", (string) $archivedProject->id)
        ->call('moveProject', $todo->id)
        ->assertHasErrors(["projectMoves.{$todo->id}"]);

    expect($todo->refresh()->project_id)->toBe($ownedProject->id);
});

test('board movement rejects invalid statuses and foreign task ids without leaking data', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $own = Todo::factory()->for($owner)->create();
    $foreign = Todo::factory()->for($other)->create();

    Livewire::actingAs($owner)
        ->test(Board::class)
        ->call('moveToStatus', $own->id, TodoStatus::Trash->value)
        ->assertHasErrors(['targetStatus']);

    expect(fn () => Livewire::actingAs($owner)
        ->test(Board::class)
        ->call('moveToStatus', $foreign->id, TodoStatus::Completed->value))
        ->toThrow(ModelNotFoundException::class);

    expect($own->refresh()->status())->toBe(TodoStatus::Active)
        ->and($foreign->refresh()->status())->toBe(TodoStatus::Active);
});

test('board action rejects trash target before mutating organization', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $todo = Todo::factory()->for($user)->create();

    expect(fn () => app(MoveTodoOnBoard::class)->handle($user, $todo, TodoStatus::Trash, $project->id))
        ->toThrow(ValidationException::class);

    expect($todo->refresh()->status())->toBe(TodoStatus::Active)
        ->and($todo->project_id)->toBeNull();
});

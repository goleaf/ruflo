<?php

use App\Actions\Todos\CreateTodo;
use App\Data\Todos\TodoData;
use App\Enums\Priority;
use App\Livewire\Todos\Index;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Todos\TodoFilters;
use App\Queries\Todos\TodoListQuery;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('priority enum exposes translated labels badge colors weights and values', function () {
    expect(Priority::values())->toBe(['low', 'normal', 'high', 'urgent'])
        ->and(Priority::Low->label())->toBe(__('todos.priority.low'))
        ->and(Priority::Normal->label())->toBe(__('todos.priority.normal'))
        ->and(Priority::High->label())->toBe(__('todos.priority.high'))
        ->and(Priority::Urgent->label())->toBe(__('todos.priority.urgent'))
        ->and(Priority::Low->color())->toBe('zinc')
        ->and(Priority::Normal->color())->toBe('blue')
        ->and(Priority::High->color())->toBe('amber')
        ->and(Priority::Urgent->color())->toBe('red')
        ->and(Priority::Low->weight())->toBe(0)
        ->and(Priority::Normal->weight())->toBe(1)
        ->and(Priority::High->weight())->toBe(2)
        ->and(Priority::Urgent->weight())->toBe(3);
});

test('livewire create and edit forms reject priorities outside the enum', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->priority(Priority::High)->create(['title' => 'Keep priority']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('form.title', 'Invalid create priority')
        ->set('form.priority', 'apocalyptic')
        ->call('createTodo')
        ->assertHasErrors(['form.priority']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('startEdit', $todo->id)
        ->set('editForm.priority', 'apocalyptic')
        ->call('saveEdit')
        ->assertHasErrors(['editForm.priority'])
        ->assertSet('showEditModal', true);

    expect($user->todos()->where('title', 'Invalid create priority')->exists())->toBeFalse()
        ->and($todo->fresh()->priority)->toBe(Priority::High);
});

test('todo data rejects invalid priorities when validation is bypassed', function () {
    expect(fn () => TodoData::fromArray([
        'title' => 'Do not coerce this',
        'priority' => 'apocalyptic',
    ]))->toThrow(ValidationException::class);
});

test('direct task creation keeps missing priority normal but rejects invalid priority data', function () {
    $user = User::factory()->create();

    $todo = app(CreateTodo::class)->handle($user, TodoData::fromArray([
        'title' => 'Default priority',
    ]));

    expect($todo->priority)->toBe(Priority::Normal);

    expect(fn () => app(CreateTodo::class)->handle($user, TodoData::fromArray([
        'title' => 'Invalid direct priority',
        'priority' => 'apocalyptic',
    ])))->toThrow(ValidationException::class);

    expect($user->todos()->where('title', 'Invalid direct priority')->exists())->toBeFalse();
});

test('priority filtering and sorting stay owner scoped', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    Todo::factory()->for($owner)->priority(Priority::Low)->create(['title' => 'Owner low']);
    Todo::factory()->for($owner)->priority(Priority::High)->create(['title' => 'Owner high']);
    Todo::factory()->for($owner)->priority(Priority::Urgent)->create(['title' => 'Owner urgent']);
    Todo::factory()->for($other)->priority(Priority::Urgent)->create(['title' => 'Other urgent']);

    $filtered = app(TodoListQuery::class)
        ->filtered($owner, new TodoFilters(priority: Priority::Urgent))
        ->pluck('title')
        ->all();

    $sorted = app(TodoListQuery::class)
        ->filtered($owner, new TodoFilters(sort: 'priority', direction: 'desc'))
        ->pluck('title')
        ->all();

    expect($filtered)->toBe(['Owner urgent'])
        ->and($sorted)->toBe(['Owner urgent', 'Owner high', 'Owner low']);
});

test('priority sort expression is generated from trusted enum weights', function () {
    expect(Priority::sortCaseSql())->toBe(
        "case priority when 'low' then 0 when 'normal' then 1 when 'high' then 2 when 'urgent' then 3 else 1 end",
    );
});

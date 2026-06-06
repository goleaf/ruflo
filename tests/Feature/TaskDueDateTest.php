<?php

use App\Actions\Todos\CreateTodo;
use App\Data\Todos\TodoData;
use App\Enums\TodoStatus;
use App\Livewire\Todos\Index;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Todos\TodoFilters;
use App\Queries\Todos\TodoListQuery;
use App\Rules\Todos\DueDate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('due date rule accepts only canonical date strings', function () {
    $valid = Validator::make(
        ['due_date' => '2026-03-10'],
        ['due_date' => ['nullable', 'string', new DueDate]],
    );

    $invalid = Validator::make(
        ['due_date' => '2026-02-31'],
        ['due_date' => ['nullable', 'string', new DueDate]],
    );

    expect($valid->passes())->toBeTrue()
        ->and($invalid->passes())->toBeFalse()
        ->and($invalid->errors()->first('due_date'))->toBe(__('todos.validation.due_date'));
});

test('livewire create and edit forms reject invalid due dates without changing tasks', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->dueOn('2026-03-10')->create(['title' => 'Keep due date']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('form.title', 'Invalid create due date')
        ->set('form.due_date', '2026-02-31')
        ->call('createTodo')
        ->assertHasErrors(['form.due_date']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('startEdit', $todo->id)
        ->set('editForm.due_date', 'next Monday')
        ->call('saveEdit')
        ->assertHasErrors(['editForm.due_date'])
        ->assertSet('showEditModal', true);

    expect($user->todos()->where('title', 'Invalid create due date')->exists())->toBeFalse()
        ->and($todo->fresh()->due_date->toDateString())->toBe('2026-03-10');
});

test('todo data normalizes missing dates and rejects invalid direct due date values', function () {
    $user = User::factory()->create();

    $withoutDate = app(CreateTodo::class)->handle($user, TodoData::fromArray([
        'title' => 'No due date',
        'due_date' => '',
    ]));

    expect($withoutDate->due_date)->toBeNull();

    expect(fn () => TodoData::fromArray([
        'title' => 'Invalid direct due date',
        'due_date' => '2026-02-31',
    ]))->toThrow(ValidationException::class);

    expect($user->todos()->where('title', 'Invalid direct due date')->exists())->toBeFalse();
});

test('date buckets use the app timezone and exclude completed archived trashed and foreign tasks', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-10 09:30:00', config('app.timezone')));

    try {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $overdue = Todo::factory()->for($owner)->dueOn('2026-03-09')->create(['title' => 'Owner overdue']);
        $today = Todo::factory()->for($owner)->dueOn('2026-03-10')->create(['title' => 'Owner today']);
        $upcoming = Todo::factory()->for($owner)->dueOn('2026-03-11')->create(['title' => 'Owner upcoming']);
        Todo::factory()->for($owner)->dueOn('2026-03-09')->completed()->create(['title' => 'Completed overdue']);
        Todo::factory()->for($owner)->dueOn('2026-03-09')->archived()->create(['title' => 'Archived overdue']);
        Todo::factory()->for($owner)->dueOn('2026-03-09')->deleted()->create(['title' => 'Deleted overdue']);
        Todo::factory()->for($other)->dueOn('2026-03-09')->create(['title' => 'Other overdue']);

        $query = app(TodoListQuery::class);

        expect($overdue->isOverdue())->toBeTrue()
            ->and($today->isDueToday())->toBeTrue()
            ->and($upcoming->isUpcoming())->toBeTrue()
            ->and($query->filtered($owner, new TodoFilters(due: 'overdue'))->pluck('title')->all())->toBe(['Owner overdue'])
            ->and($query->filtered($owner, new TodoFilters(due: 'today'))->pluck('title')->all())->toBe(['Owner today'])
            ->and($query->filtered($owner, new TodoFilters(due: 'upcoming'))->pluck('title')->all())->toBe(['Owner upcoming'])
            ->and($query->summaryFor($owner)['overdue'])->toBe(1);
    } finally {
        Carbon::setTestNow();
    }
});

test('due query parameters are sanitized and limited to the active tab', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->dueOn(today()->subDay())->create(['title' => 'Active overdue']);
    Todo::factory()->for($user)->dueOn(today()->subDay())->completed()->create(['title' => 'Completed overdue']);

    Livewire::withQueryParams(['due' => 'overdue'])
        ->actingAs($user)
        ->test(Index::class)
        ->assertSee('Active overdue')
        ->assertDontSee('Completed overdue')
        ->set('tab', TodoStatus::Completed->value)
        ->assertSee('Completed overdue')
        ->assertDontSee('Active overdue');
});

<?php

use App\Events\TodoCreated;
use App\Livewire\Todos\Index;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

test('guests are redirected to login', function () {
    $this->get(route('todos.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view their todos', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Todo::factory()->for($user)->create(['title' => 'Review the current flow']);
    Todo::factory()->for($otherUser)->create(['title' => 'Private task']);

    $this->actingAs($user)
        ->get(route('todos.index'))
        ->assertOk()
        ->assertSeeText(__('todos.pages.index.title'))
        ->assertSeeText('Review the current flow')
        ->assertDontSeeText('Private task');
});

test('users can create todos', function () {
    $user = User::factory()->create();

    Event::fake([TodoCreated::class]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('form.title', 'Prepare the foundation boundary')
        ->call('createTodo')
        ->assertHasNoErrors()
        ->assertSet('form.title', '');

    expect($user->todos()->where('title', 'Prepare the foundation boundary')->exists())->toBeTrue();

    Event::assertDispatched(
        TodoCreated::class,
        fn (TodoCreated $event): bool => $event->todo->title === 'Prepare the foundation boundary',
    );
});

test('users must provide a valid title', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('form.title', '')
        ->call('createTodo')
        ->assertHasErrors(['form.title' => 'required']);
});

test('users can complete todos', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('completeTodo', $todo->id);

    expect($todo->fresh()->is_completed)->toBeTrue();
});

test('users can delete todos', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('deleteTodo', $todo->id);

    expect(Todo::query()->find($todo->id))->toBeNull()
        ->and(Todo::withTrashed()->find($todo->id))->not->toBeNull();
});

test('users can clear completed todos', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $completedTodo = Todo::factory()->for($user)->completed()->create(['title' => 'Done']);
    Todo::factory()->for($user)->create(['title' => 'Open']);
    $otherCompletedTodo = Todo::factory()->for($otherUser)->completed()->create(['title' => 'Other done']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('clearCompleted');

    expect($user->todos()->pluck('title')->all())->toBe(['Open'])
        ->and(Todo::query()->find($completedTodo->id))->toBeNull()
        ->and($otherCompletedTodo->fresh())->not->toBeNull();
});

test('users cannot mutate another users private todos', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $todo = Todo::factory()->for($owner)->create();

    expect(fn () => Livewire::actingAs($intruder)
        ->test(Index::class)
        ->call('completeTodo', $todo->id))
        ->toThrow(ModelNotFoundException::class);

    expect($todo->fresh()->is_completed)->toBeFalse();

    expect(fn () => Livewire::actingAs($intruder)
        ->test(Index::class)
        ->call('deleteTodo', $todo->id))
        ->toThrow(ModelNotFoundException::class);

    expect($todo->fresh())->not->toBeNull();
});

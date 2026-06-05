<?php

use App\Livewire\Todos\Index;
use App\Models\Todo;
use App\Models\User;
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
        ->assertSeeText('Mini todos')
        ->assertSeeText('Review the current flow')
        ->assertDontSeeText('Private task');
});

test('users can create todos', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('title', 'Ship the mini todo app')
        ->call('createTodo')
        ->assertHasNoErrors()
        ->assertSet('title', '');

    expect($user->todos()->where('title', 'Ship the mini todo app')->exists())->toBeTrue();
});

test('users can toggle todos', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('toggleTodo', $todo->id);

    expect($todo->fresh()->is_completed)->toBeTrue();
});

test('users can delete todos', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('deleteTodo', $todo->id);

    expect($todo->fresh())->toBeNull();
});

test('users can clear completed todos', function () {
    $user = User::factory()->create();

    Todo::factory()->for($user)->completed()->create(['title' => 'Done']);
    Todo::factory()->for($user)->create(['title' => 'Open']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('clearCompleted');

    expect($user->todos()->pluck('title')->all())->toBe(['Open']);
});

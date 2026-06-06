<?php

use App\Actions\Todos\CreateTodoChecklistItem;
use App\Actions\Todos\MoveTodoChecklistItem;
use App\Actions\Todos\ToggleTodoChecklistItem;
use App\Events\TodoChecklistChanged;
use App\Exceptions\InvalidTodoTransition;
use App\Livewire\Todos\Show;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('task detail renders only the current task checklist with progress', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['title' => 'Checklist parent']);
    $completed = TodoChecklistItem::factory()->forTodo($todo)->completed()->position(1)->create(['title' => 'Confirm route']);
    $pending = TodoChecklistItem::factory()->forTodo($todo)->pending()->position(2)->create(['title' => 'Review copy']);
    $foreignTodo = Todo::factory()->for($other)->create();

    TodoChecklistItem::factory()->forTodo($foreignTodo)->create(['title' => 'Other workspace step']);

    $this->actingAs($user)
        ->get(route('todos.show', $todo))
        ->assertOk()
        ->assertSee(__('todos.checklist.heading'))
        ->assertSee(__('todos.checklist.progress', ['completed' => 1, 'total' => 2]))
        ->assertSeeInOrder([$completed->title, $pending->title])
        ->assertDontSee('Other workspace step');
});

test('users can create edit toggle move and delete their own checklist items', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();
    $first = TodoChecklistItem::factory()->forTodo($todo)->pending()->position(1)->create(['title' => 'First step']);
    $second = TodoChecklistItem::factory()->forTodo($todo)->pending()->position(2)->create(['title' => 'Second step']);

    Event::fake([TodoChecklistChanged::class]);

    Livewire::actingAs($user)
        ->test(Show::class, ['todo' => $todo->id])
        ->set('newChecklistItemTitle', '  Final review  ')
        ->call('createChecklistItem')
        ->assertHasNoErrors()
        ->call('toggleChecklistItem', $first->id)
        ->call('startEditChecklistItem', $second->id)
        ->assertSet('editingChecklistItemTitle', 'Second step')
        ->set('editingChecklistItemTitle', '  Updated second step  ')
        ->call('saveChecklistItem')
        ->assertHasNoErrors()
        ->call('moveChecklistItem', TodoChecklistItem::query()->where('title', 'Final review')->value('id'), 'up')
        ->call('deleteChecklistItem', $first->id);

    $orderedTitles = TodoChecklistItem::query()
        ->whereBelongsTo($todo)
        ->orderBy('position')
        ->pluck('title')
        ->all();

    expect($orderedTitles)->toBe(['Final review', 'Updated second step'])
        ->and($first->fresh())->toBeNull()
        ->and($second->refresh()->position)->toBe(2)
        ->and(TodoChecklistItem::query()->where('title', 'Final review')->first()->position)->toBe(1);

    Event::assertDispatched(TodoChecklistChanged::class);
});

test('checklist title validation protects Livewire and direct action calls', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(Show::class, ['todo' => $todo->id])
        ->set('newChecklistItemTitle', '   ')
        ->call('createChecklistItem')
        ->assertHasErrors(['newChecklistItemTitle']);

    expect(fn () => app(CreateTodoChecklistItem::class)->handle($user, $todo, '   '))
        ->toThrow(ValidationException::class);

    expect(TodoChecklistItem::query()->whereBelongsTo($todo)->count())->toBe(0);
});

test('foreign checklist ids cannot be manipulated from a task detail page', function () {
    $viewer = User::factory()->create();
    $owner = User::factory()->create();
    $ownTodo = Todo::factory()->for($viewer)->create();
    $foreignTodo = Todo::factory()->for($owner)->create();
    $foreignItem = TodoChecklistItem::factory()->forTodo($foreignTodo)->create();

    expect(fn () => Livewire::actingAs($viewer)
        ->test(Show::class, ['todo' => $ownTodo->id])
        ->call('toggleChecklistItem', $foreignItem->id))
        ->toThrow(ModelNotFoundException::class);
});

test('archived task checklists are visible but locked from mutation', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->archived()->create(['title' => 'Archived checklist parent']);
    $item = TodoChecklistItem::factory()->forTodo($todo)->position(1)->create(['title' => 'Preserved archived note']);

    $this->actingAs($user)
        ->get(route('todos.show', $todo))
        ->assertOk()
        ->assertSee('Preserved archived note')
        ->assertSee(__('todos.checklist.locked.heading'));

    Livewire::actingAs($user)
        ->test(Show::class, ['todo' => $todo->id])
        ->set('newChecklistItemTitle', 'New archived item')
        ->call('createChecklistItem')
        ->assertHasNoErrors();

    expect(TodoChecklistItem::query()->whereBelongsTo($todo)->count())->toBe(1);

    expect(fn () => app(CreateTodoChecklistItem::class)->handle($user, $todo, 'Direct archived item'))
        ->toThrow(InvalidTodoTransition::class);

    expect(fn () => app(ToggleTodoChecklistItem::class)->handle($user, $item, true))
        ->toThrow(InvalidTodoTransition::class);
});

test('invalid checklist movement directions are rejected by the action layer', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();
    $item = TodoChecklistItem::factory()->forTodo($todo)->create();

    expect(fn () => app(MoveTodoChecklistItem::class)->handle($user, $item, 'sideways'))
        ->toThrow(ValidationException::class);
});

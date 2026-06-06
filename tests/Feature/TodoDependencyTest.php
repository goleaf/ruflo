<?php

use App\Actions\Todos\AddTodoDependency;
use App\Actions\Todos\RemoveTodoDependency;
use App\Livewire\Todos\Blocked;
use App\Livewire\Todos\Index;
use App\Livewire\Todos\Show;
use App\Models\Todo;
use App\Models\TodoDependency;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function todoDependencyRouteMiddleware(string $routeName): array
{
    return Route::getRoutes()->getByName($routeName)?->gatherMiddleware() ?? [];
}

test('blocked route redirects guests and unverified users', function () {
    $this->get(route('todos.blocked'))->assertRedirect(route('login'));

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('todos.blocked'))
        ->assertRedirect(route('verification.notice'));
});

test('task detail can add and remove an owner scoped dependency', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['title' => 'Publish the release notes']);
    $blocker = Todo::factory()->for($user)->create(['title' => 'Finish legal review']);
    Todo::factory()->create(['title' => 'Foreign private blocker']);

    Livewire::actingAs($user)
        ->test(Show::class, ['todo' => $todo->id])
        ->assertSee(__('todos.dependencies.heading'))
        ->set('dependencyTodoId', (string) $blocker->id)
        ->call('addDependency')
        ->assertHasNoErrors()
        ->assertSet('dependencyTodoId', '');

    $dependency = TodoDependency::query()->sole();

    expect($dependency->isOwnedBy($user))->toBeTrue()
        ->and($dependency->todo->is($todo))->toBeTrue()
        ->and($dependency->blocker->is($blocker))->toBeTrue()
        ->and($todo->refresh()->isBlocked())->toBeTrue();

    Livewire::actingAs($user)
        ->test(Show::class, ['todo' => $todo->id])
        ->assertSee('Finish legal review')
        ->assertSee(__('todos.dependencies.waiting_heading'))
        ->assertSee(__('todos.dependencies.status.open'))
        ->assertDontSee('Foreign private blocker');

    Livewire::actingAs($user)
        ->test(Show::class, ['todo' => $todo->id])
        ->call('removeDependency', $dependency->id)
        ->assertHasNoErrors()
        ->assertSee(__('todos.dependencies.empty.title'));

    expect(TodoDependency::query()->count())->toBe(0);
});

test('dependency validation prevents duplicates self references and cycles', function () {
    $user = User::factory()->create();
    $first = Todo::factory()->for($user)->create(['title' => 'First']);
    $second = Todo::factory()->for($user)->create(['title' => 'Second']);
    $third = Todo::factory()->for($user)->create(['title' => 'Third']);

    app(AddTodoDependency::class)->handle($user, $first, $second->id);
    app(AddTodoDependency::class)->handle($user, $second, $third->id);

    Livewire::actingAs($user)
        ->test(Show::class, ['todo' => $first->id])
        ->set('dependencyTodoId', (string) $second->id)
        ->call('addDependency')
        ->assertHasErrors('dependencyTodoId');

    Livewire::actingAs($user)
        ->test(Show::class, ['todo' => $first->id])
        ->set('dependencyTodoId', (string) $first->id)
        ->call('addDependency')
        ->assertHasErrors('dependencyTodoId');

    expect(fn () => app(AddTodoDependency::class)->handle($user, $third, $first->id))
        ->toThrow(ValidationException::class);
});

test('blocked smart view and main blocked filter show only active tasks with open blockers', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $blocker = Todo::factory()->for($user)->create(['title' => 'Approve the plan']);
    $blocked = Todo::factory()->for($user)->create(['title' => 'Ship the blocked task']);
    $resolvedBlocker = Todo::factory()->for($user)->completed()->create(['title' => 'Already resolved']);
    $resolved = Todo::factory()->for($user)->create(['title' => 'Unblocked by completion']);
    $archived = Todo::factory()->for($user)->archived()->create(['title' => 'Archived blocked task']);
    $foreignBlocked = Todo::factory()->for($other)->create(['title' => 'Foreign blocked task']);
    $foreignBlocker = Todo::factory()->for($other)->create(['title' => 'Foreign blocker']);

    TodoDependency::factory()->forTodos($blocked, $blocker)->create();
    TodoDependency::factory()->forTodos($resolved, $resolvedBlocker)->create();
    TodoDependency::factory()->forTodos($archived, $blocker)->create();
    TodoDependency::factory()->forTodos($foreignBlocked, $foreignBlocker)->create();

    $this->actingAs($user)
        ->get(route('todos.blocked'))
        ->assertOk()
        ->assertSee(__('todos.pages.blocked.title'))
        ->assertSee('Ship the blocked task')
        ->assertSee('Approve the plan')
        ->assertSee(route('todos.show', $blocked), false)
        ->assertDontSee('Unblocked by completion')
        ->assertDontSee('Archived blocked task')
        ->assertDontSee('Foreign blocked task');

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('due', 'blocked')
        ->assertSee('Ship the blocked task')
        ->assertSee(__('todos.filters.due_chip', ['due' => __('todos.filters.blocked')]))
        ->assertSee(__('todos.dependencies.blocked_badge', ['count' => 1]))
        ->assertDontSee('Unblocked by completion')
        ->assertDontSee('Foreign blocked task');

    Livewire::actingAs($user)
        ->test(Blocked::class)
        ->assertSee('Ship the blocked task')
        ->assertSee(__('todos.dependencies.blocked_by', ['title' => 'Approve the plan']));

    $blocker->forceFill(['is_completed' => true])->save();

    Livewire::actingAs($user)
        ->test(Blocked::class)
        ->assertSee(__('todos.blocked.empty.title'))
        ->assertDontSee('Ship the blocked task');
});

test('dependency actions reject inaccessible blockers and foreign dependency deletion', function () {
    $viewer = User::factory()->create();
    $owner = User::factory()->create();
    $todo = Todo::factory()->for($viewer)->create();
    $foreignBlocker = Todo::factory()->for($owner)->create();
    $foreignWaiting = Todo::factory()->for($owner)->create();
    $foreignDependency = TodoDependency::factory()->forTodos($foreignWaiting, $foreignBlocker)->create();

    Livewire::actingAs($viewer)
        ->test(Show::class, ['todo' => $todo->id])
        ->set('dependencyTodoId', (string) $foreignBlocker->id)
        ->call('addDependency')
        ->assertHasErrors('dependencyTodoId');

    expect(fn () => app(AddTodoDependency::class)->handle($viewer, $todo, $foreignBlocker->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => app(RemoveTodoDependency::class)->handle($viewer, $foreignDependency))
        ->toThrow(AuthorizationException::class);
});

test('blocked route and components keep dependency guardrails', function () {
    $showSource = file_get_contents(app_path('Livewire/Todos/Show.php'));
    $blockedSource = file_get_contents(app_path('Livewire/Todos/Blocked.php'));
    $viewSource = file_get_contents(resource_path('views/livewire/todos/show.blade.php')).file_get_contents(resource_path('views/livewire/todos/blocked.blade.php'));

    expect(todoDependencyRouteMiddleware('todos.blocked'))
        ->toContain('auth', 'verified')
        ->and(route('todos.blocked'))->toBe('https://ruflo.test/todos/blocked')
        ->and($showSource)
        ->toContain('TodoDependencyQuery')
        ->toContain('AddTodoDependency')
        ->toContain('RemoveTodoDependency')
        ->toContain('AcyclicTodoDependency')
        ->toContain('$this->authorize')
        ->not->toContain('Todo::query()')
        ->not->toContain('TodoDependency::query()')
        ->not->toContain('->save()')
        ->and($blockedSource)
        ->toContain('blockedFor($this->currentUser()')
        ->not->toContain('Todo::query()')
        ->not->toContain('Todo::find')
        ->and($viewSource)
        ->not->toContain('@php')
        ->not->toContain('Volt')
        ->not->toContain('volt');
});

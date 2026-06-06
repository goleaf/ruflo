<?php

use App\Enums\Priority;
use App\Livewire\Todos\Show;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

function taskPrivateViewMiddleware(string $routeName): array
{
    return Route::getRoutes()->getByName($routeName)?->gatherMiddleware() ?? [];
}

test('task detail route redirects guests to login', function () {
    $todo = Todo::factory()->create();

    $this->get(route('todos.show', $todo))
        ->assertRedirect(route('login'));
});

test('task detail route redirects unverified users to verification', function () {
    $user = User::factory()->unverified()->create();
    $todo = Todo::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('todos.show', $todo))
        ->assertRedirect(route('verification.notice'));
});

test('users can view their own private task detail page', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->for($user)->create(['name' => 'Client work']);
    $tag = Tag::factory()->for($user)->create(['name' => 'review']);
    $todo = Todo::factory()
        ->forProject($project)
        ->withTags($tag)
        ->priority(Priority::Urgent)
        ->dueOn(today())
        ->create(['title' => 'Review private task view']);

    Todo::factory()->for($other)->create(['title' => 'Other workspace task']);

    $this->actingAs($user)
        ->get(route('todos.show', $todo))
        ->assertOk()
        ->assertSee(__('todos.pages.show.title'))
        ->assertSee('Review private task view')
        ->assertSee('Client work')
        ->assertSee('#review')
        ->assertSee(Priority::Urgent->label())
        ->assertSee($todo->status()->label())
        ->assertDontSee('Other workspace task');
});

test('foreign task detail pages return not found without leaking content', function () {
    $viewer = User::factory()->create();
    $owner = User::factory()->create();
    $foreignTodo = Todo::factory()->for($owner)->create(['title' => 'Private foreign task']);

    $this->actingAs($viewer)
        ->get(route('todos.show', $foreignTodo))
        ->assertNotFound()
        ->assertDontSee('Private foreign task');
});

test('task list renders only current user task detail links', function () {
    $viewer = User::factory()->create();
    $other = User::factory()->create();
    $ownTodo = Todo::factory()->for($viewer)->create(['title' => 'Visible task']);
    $foreignTodo = Todo::factory()->for($other)->create(['title' => 'Hidden task']);

    $this->actingAs($viewer)
        ->get(route('todos.index'))
        ->assertOk()
        ->assertSee('Visible task')
        ->assertSee(route('todos.show', $ownTodo), false)
        ->assertDontSee('Hidden task')
        ->assertDontSee(route('todos.show', $foreignTodo), false);
});

test('task detail Livewire component denies direct foreign ids', function () {
    $viewer = User::factory()->create();
    $owner = User::factory()->create();
    $foreignTodo = Todo::factory()->for($owner)->create();

    expect(fn () => Livewire::actingAs($viewer)->test(Show::class, ['todo' => $foreignTodo->id]))
        ->toThrow(ModelNotFoundException::class);
});

test('task detail route and component keep private view guardrails', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Show.php'));

    expect(taskPrivateViewMiddleware('todos.show'))
        ->toContain('auth', 'verified')
        ->and($source)
        ->toContain('#[Locked]')
        ->toContain('public int $todoId')
        ->toContain('findVisibleFor($this->currentUser()')
        ->not->toContain('Todo::find')
        ->not->toContain('Todo::query()');
});

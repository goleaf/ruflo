<?php

use App\Enums\Priority;
use App\Livewire\Todos\Today;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

function todayRouteMiddleware(string $routeName): array
{
    return Route::getRoutes()->getByName($routeName)?->gatherMiddleware() ?? [];
}

test('today route redirects guests and unverified users', function () {
    $this->get(route('todos.today'))->assertRedirect(route('login'));

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('todos.today'))
        ->assertRedirect(route('verification.notice'));
});

test('today view renders only current user active tasks due today', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-10 09:30:00', config('app.timezone')));

    try {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $project = Project::factory()->for($user)->create(['name' => 'Launch']);
        $tag = Tag::factory()->for($user)->create(['name' => 'focus']);

        $today = Todo::factory()
            ->forProject($project)
            ->withTags($tag)
            ->priority(Priority::Urgent)
            ->dueOn('2026-03-10')
            ->create(['title' => 'Ship today view']);

        Todo::factory()->for($user)->dueOn('2026-03-09')->create(['title' => 'Yesterday task']);
        Todo::factory()->for($user)->dueOn('2026-03-11')->create(['title' => 'Tomorrow task']);
        Todo::factory()->for($user)->dueOn('2026-03-10')->completed()->create(['title' => 'Completed today']);
        Todo::factory()->for($user)->dueOn('2026-03-10')->archived()->create(['title' => 'Archived today']);
        Todo::factory()->for($user)->dueOn('2026-03-10')->deleted()->create(['title' => 'Deleted today']);
        Todo::factory()->for($other)->dueOn('2026-03-10')->create(['title' => 'Other today']);

        $this->actingAs($user)
            ->get(route('todos.today'))
            ->assertOk()
            ->assertSee(__('todos.pages.today.title'))
            ->assertSee('Ship today view')
            ->assertSee('Launch')
            ->assertSee('#focus')
            ->assertSee(Priority::Urgent->label())
            ->assertSee(route('todos.show', $today), false)
            ->assertSee(route('todos.index', ['due' => 'today']), false)
            ->assertDontSee('Yesterday task')
            ->assertDontSee('Tomorrow task')
            ->assertDontSee('Completed today')
            ->assertDontSee('Archived today')
            ->assertDontSee('Deleted today')
            ->assertDontSee('Other today');
    } finally {
        Carbon::setTestNow();
    }
});

test('today view renders translated empty state', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('todos.today'))
        ->assertOk()
        ->assertSee(__('todos.empty.due.today.title'))
        ->assertSee(__('todos.today.empty_description'));
});

test('today view complete action is limited to due today tasks', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-10 09:30:00', config('app.timezone')));

    try {
        $user = User::factory()->create();
        $today = Todo::factory()->for($user)->dueOn('2026-03-10')->create();
        $tomorrow = Todo::factory()->for($user)->dueOn('2026-03-11')->create();

        Livewire::actingAs($user)
            ->test(Today::class)
            ->call('completeTodo', $today->id)
            ->assertHasNoErrors();

        expect($today->fresh()->is_completed)->toBeTrue()
            ->and($tomorrow->fresh()->is_completed)->toBeFalse();

        expect(fn () => Livewire::actingAs($user)
            ->test(Today::class)
            ->call('completeTodo', $tomorrow->id))
            ->toThrow(ModelNotFoundException::class);
    } finally {
        Carbon::setTestNow();
    }
});

test('today route and component keep private view guardrails', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Today.php'));

    expect(todayRouteMiddleware('todos.today'))
        ->toContain('auth', 'verified')
        ->and(route('todos.today'))->toBe('https://ruflo.test/todos/today')
        ->and($source)
        ->toContain('todayFor($this->currentUser()')
        ->toContain('findTodayFor($this->currentUser()')
        ->not->toContain('Todo::query()')
        ->not->toContain('Todo::find');
});

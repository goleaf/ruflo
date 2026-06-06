<?php

use App\Enums\Priority;
use App\Livewire\Todos\Overdue;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

function overdueRouteMiddleware(string $routeName): array
{
    return Route::getRoutes()->getByName($routeName)?->gatherMiddleware() ?? [];
}

test('overdue route redirects guests and unverified users', function () {
    $this->get(route('todos.overdue'))->assertRedirect(route('login'));

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('todos.overdue'))
        ->assertRedirect(route('verification.notice'));
});

test('overdue view renders only current user active tasks past due', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-10 09:30:00', config('app.timezone')));

    try {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $project = Project::factory()->for($user)->create(['name' => 'Rescue']);
        $tag = Tag::factory()->for($user)->create(['name' => 'late']);

        $overdue = Todo::factory()
            ->forProject($project)
            ->withTags($tag)
            ->priority(Priority::Urgent)
            ->dueOn('2026-03-09')
            ->create(['title' => 'Fix overdue view']);

        Todo::factory()->for($user)->dueOn('2026-03-10')->create(['title' => 'Today task']);
        Todo::factory()->for($user)->dueOn('2026-03-11')->create(['title' => 'Tomorrow task']);
        Todo::factory()->for($user)->dueOn('2026-03-09')->completed()->create(['title' => 'Completed overdue']);
        Todo::factory()->for($user)->dueOn('2026-03-09')->archived()->create(['title' => 'Archived overdue']);
        Todo::factory()->for($user)->dueOn('2026-03-09')->deleted()->create(['title' => 'Deleted overdue']);
        Todo::factory()->for($other)->dueOn('2026-03-09')->create(['title' => 'Other overdue']);

        $this->actingAs($user)
            ->get(route('todos.overdue'))
            ->assertOk()
            ->assertSee(__('todos.pages.overdue.title'))
            ->assertSee('Fix overdue view')
            ->assertSee('Rescue')
            ->assertSee('#late')
            ->assertSee(Priority::Urgent->label())
            ->assertSee(route('todos.show', $overdue), false)
            ->assertSee(route('todos.index', ['due' => 'overdue']), false)
            ->assertDontSee('Today task')
            ->assertDontSee('Tomorrow task')
            ->assertDontSee('Completed overdue')
            ->assertDontSee('Archived overdue')
            ->assertDontSee('Deleted overdue')
            ->assertDontSee('Other overdue');
    } finally {
        Carbon::setTestNow();
    }
});

test('overdue view renders translated empty state', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('todos.overdue'))
        ->assertOk()
        ->assertSee(__('todos.empty.due.overdue.title'))
        ->assertSee(__('todos.overdue.empty_description'));
});

test('overdue view complete action is limited to overdue tasks', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-10 09:30:00', config('app.timezone')));

    try {
        $user = User::factory()->create();
        $overdue = Todo::factory()->for($user)->dueOn('2026-03-09')->create();
        $today = Todo::factory()->for($user)->dueOn('2026-03-10')->create();

        Livewire::actingAs($user)
            ->test(Overdue::class)
            ->call('completeTodo', $overdue->id)
            ->assertHasNoErrors();

        expect($overdue->fresh()->is_completed)->toBeTrue()
            ->and($today->fresh()->is_completed)->toBeFalse();

        expect(fn () => Livewire::actingAs($user)
            ->test(Overdue::class)
            ->call('completeTodo', $today->id))
            ->toThrow(ModelNotFoundException::class);
    } finally {
        Carbon::setTestNow();
    }
});

test('overdue route and component keep private view guardrails', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Overdue.php'));

    expect(overdueRouteMiddleware('todos.overdue'))
        ->toContain('auth', 'verified')
        ->and(route('todos.overdue'))->toBe('https://ruflo.test/todos/overdue')
        ->and($source)
        ->toContain('overdueFor($this->currentUser()')
        ->toContain('findOverdueFor($this->currentUser()')
        ->not->toContain('Todo::query()')
        ->not->toContain('Todo::find');
});

<?php

use App\Enums\Priority;
use App\Livewire\Todos\Upcoming;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

function upcomingRouteMiddleware(string $routeName): array
{
    return Route::getRoutes()->getByName($routeName)?->gatherMiddleware() ?? [];
}

test('upcoming route redirects guests and unverified users', function () {
    $this->get(route('todos.upcoming'))->assertRedirect(route('login'));

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('todos.upcoming'))
        ->assertRedirect(route('verification.notice'));
});

test('upcoming view renders only current user active future tasks', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-10 09:30:00', config('app.timezone')));

    try {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $project = Project::factory()->for($user)->create(['name' => 'Planning']);
        $tag = Tag::factory()->for($user)->create(['name' => 'next']);

        $tomorrow = Todo::factory()
            ->forProject($project)
            ->withTags($tag)
            ->priority(Priority::High)
            ->dueOn('2026-03-11')
            ->create(['title' => 'Plan upcoming route']);

        Todo::factory()->for($user)->priority(Priority::Urgent)->dueOn('2026-03-14')->create(['title' => 'Review future batch']);
        Todo::factory()->for($user)->dueOn('2026-03-10')->create(['title' => 'Today task']);
        Todo::factory()->for($user)->dueOn('2026-03-09')->create(['title' => 'Overdue task']);
        Todo::factory()->for($user)->create(['title' => 'No due date task']);
        Todo::factory()->for($user)->dueOn('2026-03-11')->completed()->create(['title' => 'Completed upcoming']);
        Todo::factory()->for($user)->dueOn('2026-03-11')->archived()->create(['title' => 'Archived upcoming']);
        Todo::factory()->for($user)->dueOn('2026-03-11')->deleted()->create(['title' => 'Deleted upcoming']);
        Todo::factory()->for($other)->dueOn('2026-03-11')->create(['title' => 'Other upcoming']);

        $this->actingAs($user)
            ->get(route('todos.upcoming'))
            ->assertOk()
            ->assertSee(__('todos.pages.upcoming.title'))
            ->assertSee('Plan upcoming route')
            ->assertSee('Review future batch')
            ->assertSeeInOrder(['Plan upcoming route', 'Review future batch'])
            ->assertSee('Planning')
            ->assertSee('#next')
            ->assertSee(Priority::High->label())
            ->assertSee(route('todos.show', $tomorrow), false)
            ->assertSee(route('todos.index', ['due' => 'upcoming']), false)
            ->assertDontSee('Today task')
            ->assertDontSee('Overdue task')
            ->assertDontSee('No due date task')
            ->assertDontSee('Completed upcoming')
            ->assertDontSee('Archived upcoming')
            ->assertDontSee('Deleted upcoming')
            ->assertDontSee('Other upcoming');
    } finally {
        Carbon::setTestNow();
    }
});

test('upcoming view renders translated empty state', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('todos.upcoming'))
        ->assertOk()
        ->assertSee(__('todos.empty.due.upcoming.title'))
        ->assertSee(__('todos.upcoming.empty_description'));
});

test('upcoming view complete action is limited to upcoming tasks', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-10 09:30:00', config('app.timezone')));

    try {
        $user = User::factory()->create();
        $upcoming = Todo::factory()->for($user)->dueOn('2026-03-11')->create();
        $today = Todo::factory()->for($user)->dueOn('2026-03-10')->create();

        Livewire::actingAs($user)
            ->test(Upcoming::class)
            ->call('completeTodo', $upcoming->id)
            ->assertHasNoErrors();

        expect($upcoming->fresh()->is_completed)->toBeTrue()
            ->and($today->fresh()->is_completed)->toBeFalse();

        expect(fn () => Livewire::actingAs($user)
            ->test(Upcoming::class)
            ->call('completeTodo', $today->id))
            ->toThrow(ModelNotFoundException::class);
    } finally {
        Carbon::setTestNow();
    }
});

test('upcoming route and component keep private view guardrails', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Upcoming.php'));

    expect(upcomingRouteMiddleware('todos.upcoming'))
        ->toContain('auth', 'verified')
        ->and(route('todos.upcoming'))->toBe('https://ruflo.test/todos/upcoming')
        ->and($source)
        ->toContain('upcomingFor($this->currentUser()')
        ->toContain('findUpcomingFor($this->currentUser()')
        ->not->toContain('Todo::query()')
        ->not->toContain('Todo::find');
});

<?php

use App\Data\Todos\TodoCleanupFilters;
use App\Enums\Priority;
use App\Livewire\Todos\Cleanup;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\TodoDependency;
use App\Models\User;
use App\Queries\Todos\TodoCleanupQuery;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

function cleanupRouteMiddleware(string $routeName): array
{
    return Route::getRoutes()->getByName($routeName)?->gatherMiddleware() ?? [];
}

test('cleanup route redirects guests and unverified users', function () {
    $this->get(route('todos.cleanup'))->assertRedirect(route('login'));

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('todos.cleanup'))
        ->assertRedirect(route('verification.notice'));
});

test('default stale view is owner scoped', function () {
    Carbon::setTestNow('2026-03-10 12:00:00');

    $user = User::factory()->create();
    $other = User::factory()->create();

    $stale = Todo::factory()->for($user)->create([
        'title' => 'Refresh stale project note',
        'updated_at' => now()->subDays(20),
        'created_at' => now()->subDays(20),
    ]);

    Todo::factory()->for($user)->create([
        'title' => 'Fresh project note',
        'updated_at' => now()->subDay(),
    ]);

    Todo::factory()->for($other)->create([
        'title' => 'Foreign stale note',
        'updated_at' => now()->subDays(20),
    ]);

    $this->actingAs($user)
        ->get(route('todos.cleanup'))
        ->assertOk()
        ->assertSee(__('todos.pages.cleanup.title'))
        ->assertSee('Refresh stale project note')
        ->assertSee(route('todos.show', $stale), false)
        ->assertSee(__('todos.cleanup.filters.view_chip', ['view' => __('todos.cleanup.views.stale')]))
        ->assertDontSee('Fresh project note')
        ->assertDontSee('Foreign stale note');

    Carbon::setTestNow();
});

test('cleanup view buckets isolate unplanned blocked and risky tasks', function () {
    Carbon::setTestNow('2026-03-10 12:00:00');

    $user = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $tag = Tag::factory()->for($user)->create();

    $unplanned = Todo::factory()->for($user)->create([
        'title' => 'Choose unplanned next step',
        'priority' => Priority::Low,
    ]);

    Todo::factory()->for($user)->inbox()->create([
        'title' => 'Captured inbox task',
    ]);

    Todo::factory()->for($user)->withTags($tag)->create([
        'title' => 'Tagged loose task',
    ]);

    $blocked = Todo::factory()->for($user)->dueToday()->create([
        'project_id' => $project->id,
        'title' => 'Blocked cleanup task',
    ]);
    $blocker = Todo::factory()->for($user)->create([
        'project_id' => $project->id,
        'title' => 'Unfinished blocker',
    ]);
    TodoDependency::factory()->forTodos($blocked, $blocker)->create();

    $urgent = Todo::factory()->for($user)->urgentPriority()->create([
        'project_id' => $project->id,
        'title' => 'Urgent undated risk',
    ]);

    $highOverdue = Todo::factory()->for($user)->highPriority()->overdue()->create([
        'project_id' => $project->id,
        'title' => 'High overdue risk',
    ]);

    Todo::factory()->for($other)->create([
        'title' => 'Foreign unplanned task',
    ]);

    Livewire::actingAs($user)
        ->test(Cleanup::class)
        ->set('view', TodoCleanupFilters::Unplanned)
        ->assertSee('Choose unplanned next step')
        ->assertSee(route('todos.show', $unplanned), false)
        ->assertDontSee('Captured inbox task')
        ->assertDontSee('Tagged loose task')
        ->assertDontSee('Foreign unplanned task')
        ->set('view', TodoCleanupFilters::Blocked)
        ->assertSee('Blocked cleanup task')
        ->assertSee(__('todos.dependencies.blocked_badge', ['count' => 1]))
        ->assertDontSee('Urgent undated risk')
        ->set('view', TodoCleanupFilters::Risky)
        ->assertSee('Blocked cleanup task')
        ->assertSee('Urgent undated risk')
        ->assertSee('High overdue risk');

    expect(app(TodoCleanupQuery::class)->summaryFor($user))
        ->toMatchArray([
            TodoCleanupFilters::Unplanned => 1,
            TodoCleanupFilters::Blocked => 1,
            TodoCleanupFilters::Risky => 3,
        ]);

    Carbon::setTestNow();
});

test('cleanup search sort pagination and reset stay url backed', function () {
    Carbon::setTestNow('2026-03-10 12:00:00');

    $user = User::factory()->create();

    foreach (range(1, 13) as $number) {
        Todo::factory()->for($user)->create([
            'title' => sprintf('Cleanup alpha %02d', $number),
            'updated_at' => now()->subDays(20 + $number),
            'created_at' => now()->subDays(20 + $number),
        ]);
    }

    $this->actingAs($user)
        ->get(route('todos.cleanup', [
            'view' => TodoCleanupFilters::Stale,
            'search' => 'Cleanup alpha',
            'sort' => TodoCleanupFilters::TitleSort,
            'direction' => 'asc',
            'page' => 2,
        ]))
        ->assertOk()
        ->assertSee('Cleanup alpha 13')
        ->assertDontSee('Cleanup alpha 01');

    Livewire::actingAs($user)
        ->withQueryParams([
            'view' => TodoCleanupFilters::Stale,
            'search' => 'alpha',
            'sort' => TodoCleanupFilters::TitleSort,
            'direction' => 'asc',
            'page' => 2,
        ])
        ->test(Cleanup::class)
        ->assertSet('search', 'alpha')
        ->assertSee(__('todos.filters.search_chip', ['term' => 'alpha']))
        ->assertSee(__('todos.filters.sort_chip', ['sort' => __('todos.cleanup.sort.title')]))
        ->call('resetFilters')
        ->assertSet('view', TodoCleanupFilters::Stale)
        ->assertSet('search', '')
        ->assertSet('sort', TodoCleanupFilters::RiskSort)
        ->assertSet('direction', 'desc')
        ->assertSee('Cleanup alpha');

    Carbon::setTestNow();
});

test('cleanup invalid query parameters fail closed', function () {
    Carbon::setTestNow('2026-03-10 12:00:00');

    $user = User::factory()->create();

    Todo::factory()->for($user)->create([
        'title' => 'Hidden stale cleanup task',
        'updated_at' => now()->subDays(20),
    ]);

    Livewire::actingAs($user)
        ->withQueryParams([
            'view' => 'foreign',
            'sort' => 'raw',
            'direction' => 'sideways',
        ])
        ->test(Cleanup::class)
        ->assertSee(__('todos.cleanup.empty.invalid.title'))
        ->assertSee(__('todos.cleanup.descriptions.invalid'))
        ->assertSee(__('todos.filters.sort_chip', ['sort' => __('todos.filters.unavailable_filter')]))
        ->assertSee(__('todos.filters.direction_chip', ['direction' => __('todos.filters.unavailable_filter')]))
        ->assertDontSee('Hidden stale cleanup task');

    Carbon::setTestNow();
});

test('cleanup route and component keep class based guardrails', function () {
    $componentSource = file_get_contents(app_path('Livewire/Todos/Cleanup.php'));
    $viewSource = file_get_contents(resource_path('views/livewire/todos/cleanup.blade.php'));

    expect(cleanupRouteMiddleware('todos.cleanup'))
        ->toContain('auth', 'verified')
        ->and(route('todos.cleanup'))->toBe('https://ruflo.test/todos/cleanup')
        ->and(class_exists(Cleanup::class))->toBeTrue()
        ->and(file_exists(resource_path('views/components/⚡todos/cleanup.blade.php')))->toBeFalse()
        ->and(file_exists(resource_path('views/livewire/todos/cleanup.blade.php')))->toBeTrue()
        ->and($componentSource)
        ->toContain('TodoCleanupQuery')
        ->toContain('TodoCleanupFilters')
        ->toContain('$this->authorize')
        ->not->toContain('Todo::query()')
        ->not->toContain('TodoDependency::query()')
        ->not->toContain('->save()')
        ->and($viewSource)
        ->toContain('wire:model.live.debounce.300ms="search"')
        ->toContain('flux:pagination')
        ->not->toContain('@php')
        ->not->toContain('<?php');
});

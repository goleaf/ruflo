<?php

use App\Actions\Goals\LinkTodoToGoal;
use App\Data\Goals\GoalProgress;
use App\Livewire\Goals\Create as GoalsCreate;
use App\Livewire\Goals\CreateMilestone as GoalsCreateMilestone;
use App\Livewire\Goals\Index as GoalsIndex;
use App\Models\Goal;
use App\Models\GoalMilestone;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Goals\GoalListQuery;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('goals routes redirect guests and unverified users', function (string $routeName) {
    $this->get(route($routeName))
        ->assertRedirect(route('login'));

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route($routeName))
        ->assertRedirect(route('verification.notice'));
})->with([
    'goals index' => 'goals.index',
    'create goal' => 'goals.create',
    'create milestone' => 'goals.milestones.create',
]);

test('goals render owner scoped milestones tasks and honest progress', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->for($user)->work()->create();
    $goal = Goal::factory()->forProject($project)->titled('Launch private planning')->create([
        'description' => 'Owner visible goal',
    ]);
    $completedMilestone = GoalMilestone::factory()->forGoal($goal)->completed()->position(1)->create([
        'title' => 'Confirm scope',
    ]);
    $pendingMilestone = GoalMilestone::factory()->forGoal($goal)->pending()->position(2)->create([
        'title' => 'Ship outcome',
    ]);
    Todo::factory()->forGoal($goal)->completed()->create(['title' => 'Completed goal task']);
    Todo::factory()->forMilestone($pendingMilestone)->active()->create(['title' => 'Pending milestone task']);
    Todo::factory()->forMilestone($completedMilestone)->deleted()->create(['title' => 'Deleted milestone task']);

    Goal::factory()->for($other)->titled('Foreign goal')->create();

    $progress = GoalProgress::forGoal(app(GoalListQuery::class)->findFor($user, $goal->id));

    expect($progress->completedUnits)->toBe(2)
        ->and($progress->totalUnits)->toBe(4)
        ->and($progress->percent)->toBe(50);

    Livewire::actingAs($user)->test(GoalsIndex::class)
        ->assertSee('Launch private planning')
        ->assertSee('Work')
        ->assertSee('Confirm scope')
        ->assertSee('Ship outcome')
        ->assertSee(__('goals.progress.text', ['completed' => 2, 'total' => 4, 'percent' => 50]))
        ->assertDontSee('Foreign goal')
        ->assertDontSee('Deleted milestone task');
});

test('goal creation validates translated input and owner scoped projects', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->work()->create();
    $foreignProject = Project::factory()->create();

    Livewire::actingAs($user)->test(GoalsCreate::class)
        ->set('title', '   ')
        ->call('createGoal')
        ->assertHasErrors(['title'])
        ->set('title', '  Improve onboarding  ')
        ->set('projectId', (string) $foreignProject->id)
        ->call('createGoal')
        ->assertHasErrors(['project_id'])
        ->set('projectId', (string) $project->id)
        ->set('targetDate', '2026-03-15')
        ->call('createGoal')
        ->assertHasNoErrors()
        ->assertRedirect(route('goals.index'));

    $goal = Goal::query()->whereBelongsTo($user)->where('title', 'Improve onboarding')->firstOrFail();

    expect($goal->project_id)->toBe($project->id)
        ->and($goal->target_date->toDateString())->toBe('2026-03-15');
});

test('milestones can be added checked in and reopened without spoofing another user', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();
    $foreignGoal = Goal::factory()->create();
    $foreignMilestone = GoalMilestone::factory()->create();

    Livewire::actingAs($user)->test(GoalsCreateMilestone::class)
        ->set('milestoneGoalId', (string) $foreignGoal->id)
        ->set('milestoneTitle', 'Foreign milestone')
        ->call('addMilestone')
        ->assertHasErrors(['goal_id'])
        ->set('milestoneGoalId', (string) $goal->id)
        ->set('milestoneTitle', '  First public beta  ')
        ->set('milestoneTargetDate', '2026-03-20')
        ->call('addMilestone')
        ->assertHasNoErrors()
        ->assertRedirect(route('goals.index'));

    $milestone = GoalMilestone::query()->whereBelongsTo($user)->where('title', 'First public beta')->firstOrFail();

    expect($milestone->goal_id)->toBe($goal->id)
        ->and($milestone->position)->toBe(1)
        ->and($milestone->target_date->toDateString())->toBe('2026-03-20')
        ->and($milestone->completed_at)->toBeNull();

    Livewire::actingAs($user)->test(GoalsIndex::class)
        ->call('checkInMilestone', $milestone->id)
        ->call('checkInMilestone', $milestone->id);

    expect($milestone->refresh()->completed_at)->toBeNull();

    expect(fn () => Livewire::actingAs($user)->test(GoalsIndex::class)->call('checkInMilestone', $foreignMilestone->id))
        ->toThrow(ModelNotFoundException::class);
});

test('tasks link to goals and milestones through owner scoped actions only', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();
    $milestone = GoalMilestone::factory()->forGoal($goal)->create();
    $otherGoal = Goal::factory()->for($user)->create();
    $otherMilestone = GoalMilestone::factory()->forGoal($otherGoal)->create();
    $todo = Todo::factory()->for($user)->active()->create(['title' => 'Linkable task']);
    $foreignTodo = Todo::factory()->for($other)->create();
    $archivedTodo = Todo::factory()->for($user)->archived()->create();

    Livewire::actingAs($user)->test(GoalsIndex::class)
        ->set('linkTodoIds.'.$goal->id, (string) $todo->id)
        ->set('linkMilestoneIds.'.$goal->id, (string) $milestone->id)
        ->call('linkTodo', $goal->id)
        ->assertHasNoErrors();

    expect($todo->refresh()->goal_id)->toBe($goal->id)
        ->and($todo->goal_milestone_id)->toBe($milestone->id);

    expect(fn () => app(LinkTodoToGoal::class)->handle($user, $goal, $foreignTodo))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(LinkTodoToGoal::class)->handle($user, $goal, $archivedTodo))
        ->toThrow(ValidationException::class);

    expect(fn () => app(LinkTodoToGoal::class)->handle($user, $goal, $todo, $otherMilestone))
        ->toThrow(ValidationException::class);
});

test('goals page presents workflow blocks as tabs', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('goals.index'))
        ->assertOk()
        ->assertSee('data-test="goals-tabs"', false)
        ->assertSee('role="tablist"', false)
        ->assertSee(__('goals.tabs.goals'))
        ->assertSee(__('goals.tabs.create'))
        ->assertSee(__('goals.tabs.milestones'))
        ->assertSee(route('goals.create'), false)
        ->assertSee(route('goals.milestones.create'), false)
        ->assertSee('data-test="goal-list-panel"', false)
        ->assertDontSee('data-test="goal-create"', false)
        ->assertDontSee('data-test="goal-milestone-create"', false)
        ->assertDontSee(__('goals.create.description'))
        ->assertDontSee(__('goals.milestones.create_description'));

    Livewire::actingAs($user)
        ->test(GoalsIndex::class)
        ->assertSee(__('goals.empty.title'))
        ->assertSee(__('goals.tabs.create'))
        ->assertSee(__('goals.tabs.milestones'))
        ->assertDontSee(__('goals.create.description'))
        ->assertDontSee(__('goals.milestones.create_description'));
});

test('goal creation pages render their own forms', function () {
    $user = User::factory()->create();
    Goal::factory()->for($user)->titled('Improve delivery')->create();

    $this->actingAs($user)
        ->get(route('goals.create'))
        ->assertOk()
        ->assertSee(__('goals.pages.create.title'))
        ->assertSee(__('goals.create.heading'))
        ->assertSee('data-test="goal-create"', false)
        ->assertSee('wire:submit="createGoal"', false)
        ->assertDontSee('data-test="goal-milestone-create"', false);

    $this->actingAs($user)
        ->get(route('goals.milestones.create'))
        ->assertOk()
        ->assertSee(__('goals.pages.create_milestone.title'))
        ->assertSee(__('goals.milestones.create_heading'))
        ->assertSee('Improve delivery')
        ->assertSee('data-test="goal-milestone-create"', false)
        ->assertSee('wire:submit="addMilestone"', false)
        ->assertDontSee('data-test="goal-create"', false);
});

test('goals route component and view follow architecture guardrails', function () {
    $route = Route::getRoutes()->getByName('goals.index');
    $createRoute = Route::getRoutes()->getByName('goals.create');
    $milestoneRoute = Route::getRoutes()->getByName('goals.milestones.create');
    $componentSource = file_get_contents(app_path('Livewire/Goals/Index.php'));
    $createSource = file_get_contents(app_path('Livewire/Goals/Create.php'));
    $milestoneSource = file_get_contents(app_path('Livewire/Goals/CreateMilestone.php'));
    $viewSource = file_get_contents(resource_path('views/livewire/goals/index.blade.php'));

    expect(route('goals.index'))->toBe('https://ruflo.test/goals')
        ->and(route('goals.create'))->toBe('https://ruflo.test/goals/create')
        ->and(route('goals.milestones.create'))->toBe('https://ruflo.test/goals/milestones/create')
        ->and($route?->gatherMiddleware())->toContain('auth', 'verified')
        ->and($createRoute?->gatherMiddleware())->toContain('auth', 'verified')
        ->and($milestoneRoute?->gatherMiddleware())->toContain('auth', 'verified')
        ->and($componentSource)
        ->toContain('GoalListQuery')
        ->toContain('CheckInGoalMilestone')
        ->toContain('LinkTodoToGoal')
        ->toContain('$this->authorize')
        ->not->toContain('CreateGoal')
        ->not->toContain('CreateGoalMilestone')
        ->not->toContain('Goal::query()')
        ->not->toContain('Todo::query()')
        ->not->toContain('->save()')
        ->and($createSource)
        ->toContain('CreateGoal')
        ->toContain('GoalTitle')
        ->toContain('ProjectListQuery')
        ->toContain('$this->authorize')
        ->not->toContain('Goal::query()')
        ->not->toContain('->save()')
        ->and($milestoneSource)
        ->toContain('CreateGoalMilestone')
        ->toContain('MilestoneTitle')
        ->toContain('GoalListQuery')
        ->toContain('$this->authorize')
        ->not->toContain('Goal::query()')
        ->not->toContain('->save()')
        ->and($viewSource)
        ->toContain('data-test="goals-tabs"')
        ->toContain('role="tablist"')
        ->toContain("route('goals.create')")
        ->toContain("route('goals.milestones.create')")
        ->toContain('<flux:progress')
        ->toContain('goals.progress.text')
        ->not->toContain('wire:submit="createGoal"')
        ->not->toContain('wire:submit="addMilestone"')
        ->not->toContain('@php');
});

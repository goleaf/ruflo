<?php

use App\Enums\ProjectRole;
use App\Livewire\Todos\Index as TodoIndex;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Dashboard\DailyDashboardQuery;
use App\Queries\Dashboard\DashboardFoundationQuery;
use App\Queries\Projects\ProjectListQuery;
use App\Queries\Todos\TodoFilters;
use App\Queries\Todos\TodoListQuery;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('shared project tasks participate in safe search filters and dashboard task counters', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-06 09:00:00', config('app.timezone')));

    try {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $outsider = User::factory()->create();
        $project = Project::factory()->for($owner)->create(['name' => 'Shared launch']);
        $otherProject = Project::factory()->for($outsider)->create(['name' => 'Foreign launch']);

        ProjectMembership::factory()->forProject($project)->forMember($viewer)->viewer()->create();

        Todo::factory()->forProject($project)->dueToday()->create(['title' => 'Shared launch checklist']);
        Todo::factory()->forProject($project)->completed()->create(['title' => 'Shared completed note']);
        Todo::factory()->for($owner)->dueToday()->create(['title' => 'Owner private loose task']);
        Todo::factory()->forProject($otherProject)->dueToday()->create(['title' => 'Foreign project task']);

        expect(app(ProjectListQuery::class)->activeAccessibleFor($viewer)->pluck('id')->all())
            ->toBe([$project->id])
            ->and(app(TodoListQuery::class)
                ->filtered($viewer, new TodoFilters(search: 'Shared launch'))
                ->pluck('title')
                ->all())->toBe(['Shared launch checklist'])
            ->and(app(TodoListQuery::class)
                ->filtered($viewer, new TodoFilters(projectId: $project->id))
                ->pluck('title')
                ->all())->toBe(['Shared launch checklist'])
            ->and(app(TodoListQuery::class)->summaryFor($viewer))
            ->toMatchArray([
                'active' => 1,
                'completed' => 1,
                'trash' => 0,
            ])
            ->and(app(DailyDashboardQuery::class)->for($viewer))
            ->toMatchArray([
                'active_total' => 1,
                'due_today' => 1,
            ])
            ->and(app(DashboardFoundationQuery::class)->for($viewer))
            ->toMatchArray([
                'today' => 1,
                'projects_active' => 1,
                'projects_with_active_tasks' => 1,
            ]);

        Livewire::actingAs($viewer)
            ->test(TodoIndex::class)
            ->set('search', 'Shared launch')
            ->assertSee('Shared launch checklist')
            ->assertSee(__('todos.collaboration.scope.shared'))
            ->assertSee(__('todos.filters.search_chip', ['term' => 'Shared launch']))
            ->assertDontSee('Owner private loose task')
            ->assertDontSee('Foreign project task')
            ->set('project', (string) $project->id)
            ->assertSee(__('todos.filters.project_chip', ['project' => 'Shared launch']))
            ->assertSee('Shared launch checklist');
    } finally {
        Carbon::setTestNow();
    }
});

test('removed members do not retain shared search filter or dashboard scope', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $project = Project::factory()->for($owner)->create(['name' => 'Revoked shared launch']);

    ProjectMembership::factory()->forProject($project)->forMember($viewer)->viewer()->removed()->create();

    Todo::factory()->forProject($project)->dueToday()->create(['title' => 'Revoked shared checklist']);

    expect(app(ProjectListQuery::class)->activeAccessibleFor($viewer)->pluck('id')->all())
        ->toBe([])
        ->and(app(TodoListQuery::class)
            ->filtered($viewer, new TodoFilters(search: 'Revoked shared'))
            ->pluck('title')
            ->all())->toBe([])
        ->and(app(TodoListQuery::class)
            ->filtered($viewer, new TodoFilters(projectId: $project->id))
            ->pluck('title')
            ->all())->toBe([])
        ->and(app(DailyDashboardQuery::class)->for($viewer)['due_today'])->toBe(0)
        ->and(app(DashboardFoundationQuery::class)->for($viewer)['today'])->toBe(0);

    Livewire::actingAs($viewer)
        ->test(TodoIndex::class)
        ->set('search', 'Revoked shared')
        ->assertSee(__('todos.empty.search.title'))
        ->assertDontSee('Revoked shared checklist');
});

test('shared viewers can read but cannot mutate shared tasks from list actions', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $project = Project::factory()->for($owner)->create(['name' => 'Read only shared project']);
    $todo = Todo::factory()->forProject($project)->dueToday()->create(['title' => 'Read only shared task']);

    ProjectMembership::factory()->forProject($project)->forMember($viewer)->viewer()->create();

    Livewire::actingAs($viewer)
        ->test(TodoIndex::class)
        ->set('search', 'Read only shared')
        ->assertSee('Read only shared task')
        ->assertSee(__('todos.actions.read_only'))
        ->assertDontSee('wire:click="completeTodo('.$todo->id.')"', false)
        ->assertDontSee('wire:click="startEdit('.$todo->id.')"', false);

    expect($viewer->can('complete', $todo))->toBeFalse();

    Livewire::actingAs($viewer)
        ->test(TodoIndex::class)
        ->call('completeTodo', $todo->id);

    expect($todo->fresh()->is_completed)->toBeFalse();
});

test('shared editors can complete shared tasks without gaining member management scope', function () {
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $project = Project::factory()->for($owner)->create();
    $todo = Todo::factory()->forProject($project)->dueToday()->create(['title' => 'Editable shared task']);

    ProjectMembership::factory()->forProject($project)->forMember($editor)->editor()->create();

    Livewire::actingAs($editor)
        ->test(TodoIndex::class)
        ->set('search', 'Editable shared')
        ->assertSee('Editable shared task')
        ->call('completeTodo', $todo->id)
        ->assertHasNoErrors();

    expect($todo->fresh()->is_completed)->toBeTrue()
        ->and($editor->can('manageMembers', $project))->toBeFalse()
        ->and(ProjectRole::Editor->canManageMembers())->toBeFalse();
});

<?php

use App\Enums\Priority;
use App\Livewire\Reports\Overview as ReportsOverview;
use App\Models\Habit;
use App\Models\HabitCheckIn;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Reports\ReportsOverviewQuery;
use Livewire\Livewire;

test('guests are redirected from reports overview to login', function () {
    $this->get(route('reports.overview'))
        ->assertRedirect(route('login'));
});

test('reports overview counts private trends and excludes archived deleted and foreign data', function () {
    $this->travelTo('2026-06-06 09:00:00');

    $user = User::factory()->create();
    $other = User::factory()->create();
    $previousWeekStartsOn = today()->subWeek()->startOfWeek();
    $project = Project::factory()->for($user)->active()->create(['name' => 'Launch plan', 'color' => 'blue']);
    $archivedProject = Project::factory()->for($user)->archived()->create(['name' => 'Archived reports']);
    $foreignProject = Project::factory()->for($other)->active()->create(['name' => 'Foreign reports']);

    Todo::factory()->forProject($project)->dueToday()->urgentPriority()->create(['title' => 'Prepare release checklist']);
    Todo::factory()->forProject($project)->overdue()->highPriority()->create(['title' => 'Fix report blocker']);
    Todo::factory()->for($user)->dueOn(today()->subDays(8))->priority(Priority::Urgent)->create(['title' => 'Recover old overdue task']);
    Todo::factory()->for($user)->upcoming()->create(['title' => 'Plan next report']);
    Todo::factory()->for($user)->inbox()->create(['title' => 'Captured report idea']);
    Todo::factory()->for($user)->withoutDueDate()->create(['title' => 'Unassigned task']);
    Todo::factory()->forProject($project)->completed()->create([
        'title' => 'Completed current project task',
        'updated_at' => now()->subDay(),
    ]);
    Todo::factory()->forProject($project)->completed()->create([
        'title' => 'Completed previous project task',
        'updated_at' => $previousWeekStartsOn->addDays(4),
    ]);
    Todo::factory()->for($user)->overdue()->archived()->create(['title' => 'Archived overdue task']);
    Todo::factory()->for($user)->overdue()->deleted()->create(['title' => 'Deleted overdue task']);
    Todo::factory()->forProject($archivedProject)->overdue()->archived()->create(['title' => 'Archived project task']);
    Todo::factory()->forProject($foreignProject)->overdue()->create(['title' => 'Foreign report task']);
    Todo::factory()->for($other)->upcoming()->create(['title' => 'Foreign loose task']);
    Todo::factory()->for($other)->completed()->create(['updated_at' => now()]);

    $planningHabit = Habit::factory()->for($user)->daily()->titled('Plan day')->create();
    $readingHabit = Habit::factory()->for($user)->daily()->titled('Read notes')->create();
    $archivedHabit = Habit::factory()->for($user)->archived()->titled('Archived routine')->create();
    $foreignHabit = Habit::factory()->for($other)->daily()->titled('Foreign routine')->create();

    HabitCheckIn::factory()->forHabit($planningHabit)->today()->create();
    HabitCheckIn::factory()->forHabit($planningHabit)->occurredOn(today()->subDay())->create();
    HabitCheckIn::factory()->forHabit($readingHabit)->occurredOn(today()->subDays(2))->create();
    HabitCheckIn::factory()->forHabit($planningHabit)->occurredOn($previousWeekStartsOn->addDays(4))->create();
    HabitCheckIn::factory()->forHabit($readingHabit)->occurredOn($previousWeekStartsOn->addDays(3))->create();
    HabitCheckIn::factory()->forHabit($archivedHabit)->today()->create();
    HabitCheckIn::factory()->forHabit($foreignHabit)->today()->create();

    TimeEntry::factory()->for($user)->manual(30)->create(['entry_date' => today()->toDateString()]);
    TimeEntry::factory()->for($user)->manual(90)->create(['entry_date' => today()->subDays(2)->toDateString()]);
    TimeEntry::factory()->for($user)->manual(60)->create(['entry_date' => $previousWeekStartsOn->addDays(4)->toDateString()]);
    TimeEntry::factory()->for($user)->running(15)->create();
    TimeEntry::factory()->for($user)->discarded(10)->create(['entry_date' => today()->toDateString()]);
    TimeEntry::factory()->for($other)->manual(100)->create(['entry_date' => today()->toDateString()]);

    $report = app(ReportsOverviewQuery::class)->for($user);

    expect($report['productivity'])->toMatchArray([
        'active' => 6,
        'completed_this_week' => 1,
        'completed_previous_week' => 1,
        'completion_delta' => 0,
        'due_today' => 1,
        'due_next_7_days' => 1,
        'inbox' => 1,
        'completion_percent' => 14,
    ])
        ->and($report['overdue'])->toMatchArray([
            'total' => 2,
            'high_priority' => 1,
            'urgent_priority' => 1,
            'oldest_age_days' => 8,
            'one_to_three_days' => 1,
            'four_to_seven_days' => 0,
            'eight_plus_days' => 1,
        ])
        ->and($report['habits'])->toMatchArray([
            'active' => 2,
            'checked_today' => 1,
            'check_ins_this_week' => 3,
            'check_ins_previous_week' => 2,
            'weekly_delta' => 1,
            'weekly_distinct_habits' => 2,
            'adherence_percent' => 100,
        ])
        ->and($report['projects'])->toMatchArray([
            'active' => 1,
            'with_active_tasks' => 1,
            'completed_tasks_this_week' => 1,
            'overdue_tasks' => 1,
            'no_project_active' => 4,
        ])
        ->and($report['projects']['top'])->toHaveCount(1)
        ->and($report['projects']['top'][0])->toMatchArray([
            'id' => $project->id,
            'name' => 'Launch plan',
            'active' => 2,
            'completed' => 2,
            'overdue' => 1,
            'completion_percent' => 50,
        ])
        ->and($report['time'])->toMatchArray([
            'today_seconds' => 1800,
            'week_seconds' => 7200,
            'previous_week_seconds' => 3600,
            'delta_seconds' => 3600,
            'active_timers' => 1,
        ])
        ->and(collect($report['charts']['productivity'])->pluck('key')->all())->toBe(['active', 'completed', 'due_today', 'due_next_7_days'])
        ->and(collect($report['charts']['overdue'])->pluck('key')->all())->toBe(['one_to_three_days', 'four_to_seven_days', 'eight_plus_days'])
        ->and(collect($report['charts']['habits'])->pluck('key')->all())->toBe(['active', 'checked_today', 'this_week', 'distinct'])
        ->and(collect($report['charts']['time'])->pluck('key')->all())->toBe(['today', 'this_week', 'previous_week']);

    $this->actingAs($user)
        ->get(route('reports.overview'))
        ->assertOk()
        ->assertSeeText(__('reports.pages.overview.title'))
        ->assertSeeText(__('reports.widgets.productivity.label'))
        ->assertSeeText(__('reports.widgets.habits.label'))
        ->assertSeeText(__('reports.widgets.projects.label'))
        ->assertSeeText(__('reports.widgets.time.label'))
        ->assertSeeText(__('reports.widgets.overdue.label'))
        ->assertSeeText('Launch plan')
        ->assertSee('data-test="reports-overview"', false)
        ->assertSee('data-test="reports-widget-grid"', false)
        ->assertSee('data-test="reports-widget-productivity"', false)
        ->assertSee('data-test="reports-widget-habits"', false)
        ->assertSee('data-test="reports-widget-projects"', false)
        ->assertSee('data-test="reports-widget-time"', false)
        ->assertSee('data-test="reports-widget-overdue"', false)
        ->assertSee('data-test="reports-projects-grid"', false)
        ->assertSee('data-test="reports-project-card-'.$project->id.'"', false)
        ->assertSee('data-test="reports-chart-productivity"', false)
        ->assertSee('data-test="reports-chart-overdue"', false)
        ->assertSee('data-test="reports-chart-habits"', false)
        ->assertSee('data-test="reports-chart-time"', false)
        ->assertSee('data-test="reports-privacy-note"', false)
        ->assertDontSeeText('Archived reports')
        ->assertDontSeeText('Foreign reports')
        ->assertDontSeeText('Archived project task')
        ->assertDontSeeText('Deleted overdue task')
        ->assertDontSeeText('Foreign report task')
        ->assertDontSeeText('Foreign loose task')
        ->assertDontSee('<flux:chart', false)
        ->assertDontSee('cron')
        ->assertDontSee('queue worker');
});

test('reports overview renders an empty localized state', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('reports.overview'))
        ->assertOk()
        ->assertSee('data-test="reports-empty-state"', false)
        ->assertSeeText(__('reports.empty.heading'))
        ->assertSeeText(__('reports.empty.description'))
        ->assertSee('data-test="reports-projects-empty"', false);
});

test('reports overview settings can be toggled without request input', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ReportsOverview::class)
        ->assertSet('showDetails', true)
        ->assertSet('showTrends', true)
        ->call('toggleDetails')
        ->assertSet('showDetails', false)
        ->call('toggleTrends')
        ->assertSet('showTrends', false);
});

test('reports overview keeps query logic out of Blade and the Livewire component', function () {
    $view = file_get_contents(resource_path('views/livewire/reports/overview.blade.php'));
    $component = file_get_contents(app_path('Livewire/Reports/Overview.php'));

    expect($view)
        ->not->toContain('@php')
        ->not->toContain('Todo::query')
        ->not->toContain('TimeEntry::query')
        ->and($component)
        ->toContain('ReportsOverviewQuery')
        ->not->toContain('Todo::query')
        ->not->toContain('TimeEntry::query');
});

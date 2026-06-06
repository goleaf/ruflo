<?php

use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Models\Goal;
use App\Models\GoalMilestone;
use App\Models\Habit;
use App\Models\HabitCheckIn;
use App\Models\Project;
use App\Models\Reminder;
use App\Models\Tag;
use App\Models\TimeEntry;
use App\Models\Todo;
use App\Models\TodoDependency;
use App\Models\TodoRecurrenceRule;
use App\Models\User;
use App\Queries\Dashboard\DailyDashboardQuery;
use App\Queries\Dashboard\DashboardFoundationQuery;
use App\Queries\Dashboard\ProjectProgressDashboardQuery;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->dueToday()->create();
    Todo::factory()->for($user)->overdue()->create();
    Todo::factory()->for($user)->upcoming()->create();
    Todo::factory()->for($user)->deleted()->create();
    Project::factory()->for($user)->create();
    Tag::factory()->for($user)->create();

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText('RuFlo Control Deck')
        ->assertSeeText(__('dashboard.daily.heading'))
        ->assertSeeText(__('dashboard.daily.stats.due_today'))
        ->assertSeeText(__('dashboard.daily.stats.overdue'))
        ->assertSeeText(__('dashboard.daily.stats.blocked'))
        ->assertSeeText(__('dashboard.daily.stats.due_reminders'))
        ->assertSeeText(__('dashboard.daily.stats.unread_notifications'))
        ->assertSeeText(__('dashboard.daily.stats.time_today'))
        ->assertSeeText(__('dashboard.daily.actions.notifications'))
        ->assertSeeText('Private workspace')
        ->assertSeeText('Trash')
        ->assertSeeText('Open todos')
        ->assertSeeText('Track time')
        ->assertSeeText('Blocked')
        ->assertSeeText('Cleanup')
        ->assertSeeText('Automations')
        ->assertSeeText('Reminders')
        ->assertSeeText('Today at a glance')
        ->assertSeeText('Schedule coverage')
        ->assertSeeText('Next 7 days')
        ->assertSee('data-test="dashboard-daily-summary"', false)
        ->assertSee('data-test="dashboard-daily-settings"', false)
        ->assertSee('data-test="dashboard-daily-schedule-coverage"', false)
        ->assertSee('data-test="dashboard-daily-details"', false)
        ->assertSee('data-test="dashboard-foundation-widgets"', false)
        ->assertSee('data-test="dashboard-foundation-settings"', false)
        ->assertSee('data-test="dashboard-foundation-chart"', false)
        ->assertSee('data-test="dashboard-foundation-privacy-note"', false)
        ->assertSee('data-test="dashboard-foundation-widget-today"', false)
        ->assertSee('data-test="dashboard-foundation-widget-overdue"', false)
        ->assertSee('data-test="dashboard-foundation-widget-upcoming"', false)
        ->assertSee('data-test="dashboard-foundation-widget-priorities"', false)
        ->assertSee('data-test="dashboard-foundation-widget-reminders"', false)
        ->assertSee('data-test="dashboard-foundation-widget-recurrence"', false)
        ->assertSee('data-test="dashboard-foundation-widget-goals"', false)
        ->assertSee('data-test="dashboard-foundation-widget-habits"', false)
        ->assertSee('data-test="dashboard-foundation-widget-projects"', false)
        ->assertSee('data-test="dashboard-foundation-widget-time"', false)
        ->assertSee('data-test="dashboard-workspace-tabs"', false)
        ->assertSee('data-test="dashboard-owner-tab"', false)
        ->assertSee('data-test="dashboard-workspace-tab-link"', false)
        ->assertSee('data-test="dashboard-owner-boundary-panel"', false)
        ->assertSee('role="tablist"', false)
        ->assertSee('role="tab"', false)
        ->assertSee('role="tabpanel"', false)
        ->assertSee('aria-selected="true"', false)
        ->assertSee('aria-selected="false"', false)
        ->assertSee('min-w-max', false)
        ->assertSee('whitespace-nowrap', false)
        ->assertSee('data-test="dashboard-daily-summary"', false)
        ->assertSee('data-test="dashboard-daily-attention-state"', false)
        ->assertSee('data-test="dashboard-daily-action-notifications"', false)
        ->assertSee('data-test="dashboard-summary-widgets"', false)
        ->assertSee('grid-cols-11', false)
        ->assertSee('bg-sky-50', false)
        ->assertSee('bg-rose-50', false)
        ->assertSee('bg-emerald-50', false)
        ->assertSeeInOrder([
            __('dashboard.daily.label'),
            __('dashboard.daily.heading'),
            __('dashboard.daily.stats.due_today'),
            __('dashboard.daily.stats.overdue'),
            __('dashboard.daily.stats.due_soon'),
            __('dashboard.daily.stats.unplanned'),
            __('dashboard.daily.stats.blocked'),
            __('dashboard.daily.stats.due_reminders'),
            __('dashboard.daily.stats.unread_notifications'),
            __('dashboard.daily.stats.time_today'),
            __('dashboard.daily.stats.active_timers'),
            __('dashboard.daily.schedule_coverage.label'),
            __('dashboard.daily.details.planning'),
            __('dashboard.daily.details.reminders'),
            __('dashboard.daily.details.signals'),
        ])
        ->assertSeeInOrder([
            __('dashboard.foundation.label'),
            __('dashboard.foundation.heading'),
            __('dashboard.foundation.widgets.today.label'),
            __('dashboard.foundation.widgets.overdue.label'),
            __('dashboard.foundation.widgets.upcoming.label'),
            __('dashboard.foundation.widgets.priorities.label'),
            __('dashboard.foundation.widgets.reminders.label'),
            __('dashboard.foundation.widgets.recurrence.label'),
            __('dashboard.foundation.widgets.goals.label'),
            __('dashboard.foundation.widgets.habits.label'),
            __('dashboard.foundation.widgets.projects.label'),
            __('dashboard.foundation.widgets.time.label'),
            __('dashboard.foundation.chart.label'),
            __('dashboard.foundation.privacy.heading'),
        ])
        ->assertSeeInOrder([
            __('dashboard.workspace.label'),
            __('dashboard.workspace.heading'),
            __('dashboard.workspace.today_action'),
            __('dashboard.workspace.overdue_action'),
            __('dashboard.workspace.upcoming_action'),
            __('dashboard.workspace.focus_action'),
            __('dashboard.workspace.time_action'),
            __('dashboard.workspace.blocked_action'),
            __('dashboard.workspace.cleanup_action'),
            __('dashboard.workspace.automations_action'),
            __('dashboard.workspace.reminders_action'),
            __('dashboard.workspace.goals_action'),
            __('dashboard.workspace.habits_action'),
            __('dashboard.workspace.action'),
            __('dashboard.workspace.description'),
        ])
        ->assertSeeInOrder([
            __('dashboard.summary.active'),
            __('dashboard.summary.overdue'),
            __('dashboard.summary.completed'),
            __('dashboard.summary.archived'),
            __('dashboard.summary.trash'),
            __('dashboard.summary.projects'),
            __('dashboard.summary.tags'),
            __('dashboard.summary.goals'),
            __('dashboard.summary.milestones'),
            __('dashboard.summary.habits'),
            __('dashboard.summary.habit_check_ins'),
        ])
        ->assertDontSeeText('Install paths')
        ->assertDontSeeText('cron')
        ->assertDontSeeText('daily email')
        ->assertDontSeeText('npx ruflo@latest mcp start')
        ->assertDontSeeText('/plugin marketplace add ruvnet/ruflo');
});

test('project progress dashboard counts owner active projects cleanup and no-project signals', function () {
    $this->travelTo('2026-06-06 09:00:00');

    $user = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->for($user)->active()->create(['name' => 'Launch plan', 'color' => 'blue']);
    $emptyProject = Project::factory()->for($user)->active()->create(['name' => 'Empty plan', 'color' => 'green']);
    $archivedProject = Project::factory()->for($user)->archived()->create(['name' => 'Archived plan']);
    $foreignProject = Project::factory()->for($other)->active()->create(['name' => 'Foreign plan']);

    Todo::factory()->forProject($project)->overdue()->create(['title' => 'Fix launch blocker']);
    Todo::factory()->forProject($project)->upcoming()->create(['title' => 'Prepare launch notes']);
    Todo::factory()->forProject($project)->withoutDueDate()->create([
        'title' => 'Stale launch task',
        'created_at' => now()->subDays(20),
        'updated_at' => now()->subDays(20),
    ]);
    Todo::factory()->forProject($project)->completed()->create(['title' => 'Closed launch task']);
    Todo::factory()->forProject($project)->archived()->create(['title' => 'Archived launch task']);
    Todo::factory()->forProject($project)->deleted()->create(['title' => 'Deleted launch task']);
    Todo::factory()->forProject($archivedProject)->overdue()->create(['title' => 'Archived project task']);
    Todo::factory()->forProject($foreignProject)->overdue()->create(['title' => 'Foreign project task']);
    Todo::factory()->for($other)->overdue()->create(['title' => 'Foreign loose task']);

    Todo::factory()->for($user)->overdue()->create(['title' => 'Loose deadline task']);
    Todo::factory()->for($user)->withoutDueDate()->create([
        'title' => 'Loose stale task',
        'created_at' => now()->subDays(21),
        'updated_at' => now()->subDays(21),
    ]);

    $progress = app(ProjectProgressDashboardQuery::class)->for($user);
    $projectNames = collect($progress['projects'])->pluck('name')->all();
    $launchProject = collect($progress['projects'])->firstWhere('name', 'Launch plan');
    $emptyProjectCard = collect($progress['projects'])->firstWhere('name', 'Empty plan');

    expect($progress['projects'])->toHaveCount(2)
        ->and($launchProject)->toMatchArray([
            'id' => $project->id,
            'active' => 3,
            'completed' => 1,
            'overdue' => 1,
            'due_soon' => 1,
            'undated' => 1,
            'stale' => 1,
            'total' => 4,
            'attention' => 3,
            'completion_percent' => 25,
        ])
        ->and($emptyProjectCard)->toMatchArray([
            'id' => $emptyProject->id,
            'active' => 0,
            'completed' => 0,
            'attention' => 0,
            'completion_percent' => 0,
        ])
        ->and($projectNames)->not->toContain('Archived plan')
        ->and($projectNames)->not->toContain('Foreign plan')
        ->and($progress['no_project'])->toMatchArray([
            'active' => 2,
            'overdue' => 1,
            'due_soon' => 0,
            'undated' => 1,
            'stale' => 1,
            'attention' => 3,
        ])
        ->and($progress['totals'])->toMatchArray([
            'active_projects' => 2,
            'displayed_projects' => 2,
            'active_tasks' => 3,
            'completed_tasks' => 1,
            'overdue' => 1,
            'due_soon' => 1,
            'undated' => 1,
            'stale' => 1,
            'no_project_active' => 2,
            'cleanup_signals' => 6,
        ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="dashboard-project-progress"', false)
        ->assertSee('data-test="dashboard-project-progress-cleanup"', false)
        ->assertSee('data-test="dashboard-project-progress-grid"', false)
        ->assertSee('data-test="dashboard-project-progress-card-'.$project->id.'"', false)
        ->assertSee('data-test="dashboard-project-progress-no-project"', false)
        ->assertSee(route('projects.show', $project), false)
        ->assertSee(route('todos.index', ['project' => $project->id]), false)
        ->assertSee(route('todos.index', ['project' => 'none']), false)
        ->assertSeeText('Launch plan')
        ->assertSeeText('Empty plan')
        ->assertSeeText(__('dashboard.projects.no_project.label'))
        ->assertDontSeeText('Archived plan')
        ->assertDontSeeText('Archived project task')
        ->assertDontSeeText('Foreign plan')
        ->assertDontSeeText('Foreign project task')
        ->assertDontSeeText('Foreign loose task');
});

test('project progress dashboard renders translated empty state when no project work exists', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="dashboard-project-progress"', false)
        ->assertSee('data-test="dashboard-project-progress-empty"', false)
        ->assertDontSee('data-test="dashboard-project-progress-grid"', false)
        ->assertSeeText(__('dashboard.projects.empty.heading'))
        ->assertSeeText(__('dashboard.projects.empty.description'));
});

test('dashboard foundation query counts every widget domain through the authenticated owner scope', function () {
    $this->travelTo('2026-06-06 09:00:00');

    $user = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->for($user)->active()->create();
    $archivedProject = Project::factory()->for($user)->archived()->create();

    $todayTodo = Todo::factory()->forProject($project)->dueToday()->urgentPriority()->create();
    $overdueTodo = Todo::factory()->for($user)->overdue()->highPriority()->create();
    $upcomingTodo = Todo::factory()->for($user)->upcoming()->normalPriority()->create();
    $recurringSource = Todo::factory()->for($user)->lowPriority()->create();
    Todo::factory()->forProject($archivedProject)->normalPriority()->create();
    Todo::factory()->for($user)->dueToday()->urgentPriority()->completed()->create();
    Todo::factory()->for($user)->overdue()->highPriority()->archived()->create();
    $deletedTodo = Todo::factory()->for($user)->upcoming()->normalPriority()->deleted()->create();

    Reminder::factory()->forTodo($todayTodo)->due()->create();
    Reminder::factory()->forTodo($overdueTodo)->future()->create();
    Reminder::factory()->forTodo($upcomingTodo)->processed()->create();
    Reminder::factory()->forTodo(Todo::factory()->for($user)->archived()->create())->due()->create();
    Reminder::factory()->forTodo($deletedTodo)->due()->create();

    $enabledRule = TodoRecurrenceRule::factory()->forTodo($recurringSource)->create();
    Todo::factory()->generatedOccurrence($enabledRule, '2026-06-07')->create();
    Todo::factory()->generatedOccurrence($enabledRule, '2026-06-08')->completed()->create();
    Todo::factory()->generatedOccurrence($enabledRule, '2026-06-09')->archived()->create();
    TodoRecurrenceRule::factory()->forTodo($upcomingTodo)->paused()->create();
    TodoRecurrenceRule::factory()->forTodo(Todo::factory()->for($user)->archived()->create())->create();

    $goal = Goal::factory()->for($user)->targetDate('2026-06-09')->create();
    Goal::factory()->for($user)->targetDate('2026-06-09')->completed()->create();
    $archivedGoal = Goal::factory()->for($user)->targetDate('2026-06-09')->archived()->create();
    GoalMilestone::factory()->forGoal($goal)->pending()->create();
    GoalMilestone::factory()->forGoal($goal)->completed()->create();
    GoalMilestone::factory()->forGoal($archivedGoal)->pending()->create();

    $habit = Habit::factory()->for($user)->daily()->create();
    $archivedHabit = Habit::factory()->for($user)->archived()->create();
    HabitCheckIn::factory()->forHabit($habit)->today()->create();
    HabitCheckIn::factory()->forHabit($archivedHabit)->today()->create();

    TimeEntry::factory()->forTodo($todayTodo)->manual(45)->create([
        'entry_date' => '2026-06-06',
    ]);
    TimeEntry::factory()->forTodo($overdueTodo)->manual(30)->create([
        'entry_date' => '2026-06-05',
    ]);
    TimeEntry::factory()->forTodo($upcomingTodo)->running()->create();
    TimeEntry::factory()->forTodo($upcomingTodo)->discarded(15)->create([
        'entry_date' => '2026-06-06',
    ]);

    Todo::factory()->for($other)->dueToday()->urgentPriority()->create();
    Reminder::factory()->forTodo(Todo::factory()->for($other)->create())->due()->create();
    $foreignRule = TodoRecurrenceRule::factory()->forTodo(Todo::factory()->for($other)->create())->create();
    Todo::factory()->generatedOccurrence($foreignRule, '2026-06-07')->create();
    Goal::factory()->for($other)->targetDate('2026-06-09')->create();
    HabitCheckIn::factory()->forHabit(Habit::factory()->for($other)->create())->today()->create();
    Project::factory()->for($other)->create();
    TimeEntry::factory()->forTodo(Todo::factory()->for($other)->create())->manual(60)->create([
        'entry_date' => '2026-06-06',
    ]);

    expect(app(DashboardFoundationQuery::class)->for($user))->toMatchArray([
        'today' => 1,
        'overdue' => 1,
        'upcoming' => 2,
        'priority_urgent' => 1,
        'priority_high' => 1,
        'priority_normal' => 2,
        'priority_low' => 2,
        'reminders_due' => 1,
        'reminders_pending' => 2,
        'recurrence_enabled' => 1,
        'recurrence_paused' => 1,
        'recurrence_generated' => 1,
        'goals_open' => 1,
        'goals_due_soon' => 1,
        'milestones_open' => 1,
        'habits_active' => 1,
        'habits_checked_today' => 1,
        'projects_active' => 1,
        'projects_with_active_tasks' => 1,
        'time_today_seconds' => 2700,
        'time_week_seconds' => 4500,
        'active_timers' => 1,
    ]);
});

test('dashboard foundation details can be toggled without mutating dashboard data', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardIndex::class)
        ->assertSet('showFoundationDetails', true)
        ->call('toggleFoundationDetails')
        ->assertSet('showFoundationDetails', false)
        ->assertSee(__('dashboard.foundation.heading'))
        ->assertSee(__('dashboard.foundation.widgets.today.label'));
});

test('dashboard foundation widgets can be customized without mutating private aggregate data', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->dueToday()->create();

    Livewire::actingAs($user)
        ->test(DashboardIndex::class)
        ->assertSet('foundationWidgetOrder', [
            'today',
            'overdue',
            'upcoming',
            'priorities',
            'reminders',
            'recurrence',
            'goals',
            'habits',
            'projects',
            'time',
        ])
        ->call('toggleFoundationCustomizer')
        ->assertSet('showFoundationCustomizer', true)
        ->assertSee('data-test="dashboard-foundation-customizer"', false)
        ->assertSee(__('dashboard.foundation.customize.label'))
        ->call('moveFoundationWidget', 'overdue', 'up')
        ->assertSet('foundationWidgetOrder.0', 'overdue')
        ->assertSet('foundationWidgetOrder.1', 'today')
        ->assertSeeInOrder([
            __('dashboard.foundation.widgets.overdue.label'),
            __('dashboard.foundation.widgets.today.label'),
        ])
        ->call('toggleFoundationWidget', 'today')
        ->assertSet('hiddenFoundationWidgets.0', 'today')
        ->assertDontSee('data-test="dashboard-foundation-widget-today"', false)
        ->assertSee('data-test="dashboard-foundation-setting-visible-today"', false)
        ->call('resetFoundationWidgets')
        ->assertSet('hiddenFoundationWidgets', [])
        ->assertSet('foundationWidgetOrder.0', 'today')
        ->assertSee('data-test="dashboard-foundation-widget-today"', false);

    expect(app(DashboardFoundationQuery::class)->for($user)['today'])->toBe(1);
});

test('dashboard foundation customization can hide every widget and recover with reset', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(DashboardIndex::class)
        ->call('toggleFoundationCustomizer');

    foreach ([
        'today',
        'overdue',
        'upcoming',
        'priorities',
        'reminders',
        'recurrence',
        'goals',
        'habits',
        'projects',
        'time',
    ] as $widgetKey) {
        $component->call('toggleFoundationWidget', $widgetKey);
    }

    $component
        ->assertSet('hiddenFoundationWidgets', [
            'today',
            'overdue',
            'upcoming',
            'priorities',
            'reminders',
            'recurrence',
            'goals',
            'habits',
            'projects',
            'time',
        ])
        ->assertSee('data-test="dashboard-foundation-empty-customization"', false)
        ->assertDontSee('data-test="dashboard-foundation-chart"', false)
        ->call('resetFoundationWidgets')
        ->assertSet('hiddenFoundationWidgets', [])
        ->assertSee('data-test="dashboard-foundation-widget-today"', false)
        ->assertSee('data-test="dashboard-foundation-chart"', false);
});

test('dashboard foundation customization sanitizes polluted session preferences before moving', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardIndex::class)
        ->set('foundationWidgetOrder', ['foreign-widget', 'time', 'time'])
        ->set('hiddenFoundationWidgets', ['foreign-widget', 'today', 'today'])
        ->call('moveFoundationWidget', 'time', 'down')
        ->assertHasNoErrors()
        ->assertSet('foundationWidgetOrder', [
            'today',
            'time',
            'overdue',
            'upcoming',
            'priorities',
            'reminders',
            'recurrence',
            'goals',
            'habits',
            'projects',
        ])
        ->assertSet('hiddenFoundationWidgets', ['today']);
});

test('dashboard foundation customization rejects unexpected widget input', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardIndex::class)
        ->call('toggleFoundationWidget', 'foreign-widget')
        ->assertHasErrors('foundationWidgetVisibility')
        ->call('moveFoundationWidget', 'today', 'sideways')
        ->assertHasErrors('foundationWidgetOrder');
});

test('daily dashboard summary counts only the authenticated users private actionable records', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Todo::factory()->for($user)->dueToday()->create();
    Todo::factory()->for($user)->overdue()->create();
    $blocked = Todo::factory()->for($user)->create();
    $blocker = Todo::factory()->for($user)->create();
    $dueReminderTodo = Todo::factory()->for($user)->create();
    $futureReminderTodo = Todo::factory()->for($user)->create();

    TodoDependency::factory()->forTodos($blocked, $blocker)->open()->create();
    Reminder::factory()->forTodo($dueReminderTodo)->due()->create();
    Reminder::factory()->forTodo($futureReminderTodo)->future()->create();
    TimeEntry::factory()->forTodo($dueReminderTodo)->manual(45)->create([
        'entry_date' => today()->toDateString(),
    ]);
    TimeEntry::factory()->forTodo($dueReminderTodo)->manual(20)->create([
        'entry_date' => today()->subDay()->toDateString(),
    ]);

    $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'manual',
        'data' => ['title' => 'Unread daily summary fixture'],
    ]);
    $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'manual',
        'data' => ['title' => 'Read daily summary fixture'],
        'read_at' => now(),
    ]);

    Todo::factory()->for($user)->dueToday()->completed()->create();
    Todo::factory()->for($user)->dueToday()->archived()->create();
    Todo::factory()->for($user)->dueToday()->deleted()->create();

    $foreignBlocked = Todo::factory()->for($other)->create();
    $foreignBlocker = Todo::factory()->for($other)->create();
    Todo::factory()->for($other)->dueToday()->create();
    Todo::factory()->for($other)->overdue()->create();
    TodoDependency::factory()->forTodos($foreignBlocked, $foreignBlocker)->open()->create();
    Reminder::factory()->forTodo(Todo::factory()->for($other)->create())->due()->create();
    TimeEntry::factory()->forTodo(Todo::factory()->for($other)->create())->manual(30)->create([
        'entry_date' => today()->toDateString(),
    ]);
    $other->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'manual',
        'data' => ['title' => 'Foreign unread daily summary fixture'],
    ]);

    expect(app(DailyDashboardQuery::class)->for($user))->toMatchArray([
        'date' => today()->toDateString(),
        'attention_total' => 5,
        'due_today' => 1,
        'overdue' => 1,
        'blocked' => 1,
        'due_reminders' => 1,
        'pending_reminders' => 2,
        'unread_notifications' => 1,
        'time_today_seconds' => 2700,
    ]);
});

test('daily dashboard renders a clear empty state when no attention counters exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="dashboard-daily-summary"', false)
        ->assertSee('data-test="dashboard-daily-clear-state"', false)
        ->assertSeeText(__('dashboard.daily.clear_heading'))
        ->assertSeeText(__('dashboard.daily.clear_description'));
});

test('workspace pages use the shared page container width', function () {
    $containerSource = file_get_contents(resource_path('views/components/ui/page-container.blade.php'));

    expect($containerSource)
        ->toContain('max-w-6xl')
        ->toContain('$attributes->class');

    foreach (['dashboard/index.blade.php'] as $view) {
        $source = file_get_contents(resource_path("views/livewire/{$view}"));

        expect($source)
            ->toContain('<x-ui.page-container')
            ->not->toContain('mx-auto flex w-full max-w-')
            ->not->toContain('max-w-3xl')
            ->not->toContain('max-w-4xl')
            ->not->toContain('max-w-5xl')
            ->not->toContain('max-w-6xl')
            ->not->toContain('max-w-7xl');
    }
});

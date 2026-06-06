<?php

use App\Models\Project;
use App\Models\Reminder;
use App\Models\Tag;
use App\Models\TimeEntry;
use App\Models\Todo;
use App\Models\TodoDependency;
use App\Models\User;
use App\Queries\Dashboard\DailyDashboardQuery;
use Illuminate\Support\Str;

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

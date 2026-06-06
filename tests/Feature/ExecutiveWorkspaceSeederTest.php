<?php

use App\Models\AutomationRule;
use App\Models\AutomationRuleRun;
use App\Models\Goal;
use App\Models\GoalMilestone;
use App\Models\Habit;
use App\Models\HabitCheckIn;
use App\Models\PomodoroSession;
use App\Models\Project;
use App\Models\Reminder;
use App\Models\SavedTodoView;
use App\Models\Tag;
use App\Models\TimeEntry;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\TodoDependency;
use App\Models\TodoTemplate;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\ExecutiveWorkspaceSeeder;

function assertExecutiveWorkspace(User $user, string $company): void
{
    expect($user->projects()->count())->toBeGreaterThanOrEqual(15)
        ->and($user->projects()->whereNotNull('archived_at')->count())->toBeGreaterThanOrEqual(1)
        ->and($user->tags()->count())->toBeGreaterThanOrEqual(18)
        ->and($user->goals()->count())->toBeGreaterThanOrEqual(14)
        ->and($user->goalMilestones()->count())->toBeGreaterThanOrEqual(42)
        ->and($user->goalMilestones()->whereNotNull('completed_at')->count())->toBeGreaterThanOrEqual(1)
        ->and($user->habits()->count())->toBeGreaterThanOrEqual(12)
        ->and($user->habitCheckIns()->count())->toBeGreaterThanOrEqual(120)
        ->and($user->todos()->withTrashed()->count())->toBeGreaterThanOrEqual(81)
        ->and($user->todos()->count())->toBeGreaterThanOrEqual(76)
        ->and($user->todos()->active()->count())->toBeGreaterThanOrEqual(62)
        ->and($user->todos()->completed()->count())->toBeGreaterThanOrEqual(8)
        ->and($user->todos()->archived()->count())->toBeGreaterThanOrEqual(5)
        ->and($user->todos()->onlyTrashed()->count())->toBeGreaterThanOrEqual(5)
        ->and($user->todos()->inInbox()->count())->toBeGreaterThanOrEqual(8)
        ->and($user->todos()->dueToday()->count())->toBeGreaterThanOrEqual(1)
        ->and($user->todos()->overdue()->count())->toBeGreaterThanOrEqual(2)
        ->and($user->todos()->upcoming()->count())->toBeGreaterThanOrEqual(50)
        ->and($user->todoChecklistItems()->count())->toBeGreaterThanOrEqual(216)
        ->and($user->todoChecklistItems()->where('is_completed', true)->count())->toBeGreaterThanOrEqual(15)
        ->and($user->todoDependencies()->count())->toBeGreaterThanOrEqual(22)
        ->and($user->reminders()->count())->toBeGreaterThanOrEqual(32)
        ->and($user->reminders()->due()->count())->toBeGreaterThanOrEqual(2)
        ->and($user->reminders()->whereNotNull('skipped_at')->count())->toBeGreaterThanOrEqual(4)
        ->and($user->pomodoroSessions()->count())->toBeGreaterThanOrEqual(13)
        ->and($user->timeEntries()->count())->toBeGreaterThanOrEqual(55)
        ->and($user->timeEntries()->where('status', 'running')->count())->toBeGreaterThanOrEqual(3)
        ->and($user->timeEntries()->where('status', 'discarded')->count())->toBeGreaterThanOrEqual(2)
        ->and($user->savedTodoViews()->count())->toBeGreaterThanOrEqual(16)
        ->and($user->todoTemplates()->count())->toBeGreaterThanOrEqual(16)
        ->and($user->automationRules()->count())->toBeGreaterThanOrEqual(8)
        ->and($user->automationRuleRuns()->count())->toBeGreaterThanOrEqual(16)
        ->and($user->automationRuleRuns()->where('status', 'failed')->count())->toBeGreaterThanOrEqual(2);

    $strategyTask = Todo::query()
        ->with(['goal', 'goalMilestone', 'habit', 'project', 'tags', 'checklistItems', 'reminders', 'pomodoroSessions', 'timeEntries'])
        ->where('user_id', $user->id)
        ->where('title', "Confirm {$company} board strategy narrative")
        ->firstOrFail();

    expect($strategyTask->goal?->title)->toBe("{$company} FY26 executive operating review")
        ->and($strategyTask->goalMilestone?->title)->toBe("{$company} executive brief accepted")
        ->and($strategyTask->habit?->title)->toContain($company)
        ->and($strategyTask->project?->name)->toContain($company)
        ->and($strategyTask->tags)->toHaveCount(2)
        ->and($strategyTask->checklistItems)->toHaveCount(4)
        ->and($strategyTask->reminders)->toHaveCount(1)
        ->and($strategyTask->pomodoroSessions)->toHaveCount(1)
        ->and($strategyTask->timeEntries)->toHaveCount(1)
        ->and($strategyTask->due_date->isFuture())->toBeTrue();
}

test('executive workspace seeder creates rich Apple and Microsoft demo graphs', function () {
    $this->seed([DemoUserSeeder::class, ExecutiveWorkspaceSeeder::class]);

    $apple = User::query()->where('email', 'test@example.com')->firstOrFail();
    $microsoft = User::query()->where('email', 'second@example.com')->firstOrFail();

    expect($apple->name)->toBe('Avery Chen')
        ->and($microsoft->name)->toBe('Morgan Blake');

    assertExecutiveWorkspace($apple, 'Apple');
    assertExecutiveWorkspace($microsoft, 'Microsoft');
});

test('executive workspace seeder is idempotent', function () {
    $this->seed([DemoUserSeeder::class, ExecutiveWorkspaceSeeder::class]);

    $counts = executiveWorkspaceCounts();

    $this->seed(ExecutiveWorkspaceSeeder::class);

    expect(executiveWorkspaceCounts())->toBe($counts);
});

test('executive workspace seeder respects safe demo environments', function () {
    config(['app.env' => 'production']);

    $this->seed([DemoUserSeeder::class, ExecutiveWorkspaceSeeder::class]);

    expect(User::query()->count())->toBe(0)
        ->and(Todo::query()->count())->toBe(0)
        ->and(Project::query()->count())->toBe(0)
        ->and(AutomationRuleRun::query()->count())->toBe(0);
});

/**
 * @return array<string, int>
 */
function executiveWorkspaceCounts(): array
{
    return [
        'users' => User::query()->count(),
        'projects' => Project::query()->count(),
        'tags' => Tag::query()->count(),
        'goals' => Goal::query()->count(),
        'goal_milestones' => GoalMilestone::query()->count(),
        'habits' => Habit::query()->count(),
        'habit_check_ins' => HabitCheckIn::query()->count(),
        'todos' => Todo::withTrashed()->count(),
        'checklist_items' => TodoChecklistItem::query()->count(),
        'dependencies' => TodoDependency::query()->count(),
        'reminders' => Reminder::query()->count(),
        'pomodoros' => PomodoroSession::query()->count(),
        'time_entries' => TimeEntry::query()->count(),
        'saved_views' => SavedTodoView::query()->count(),
        'templates' => TodoTemplate::query()->count(),
        'automation_rules' => AutomationRule::query()->count(),
        'automation_runs' => AutomationRuleRun::query()->count(),
    ];
}

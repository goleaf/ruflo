<?php

use App\Models\AutomationRule;
use App\Models\AutomationRuleRun;
use App\Models\Goal;
use App\Models\GoalMilestone;
use App\Models\Habit;
use App\Models\HabitCheckIn;
use App\Models\PomodoroSession;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\ProjectMembership;
use App\Models\Reminder;
use App\Models\SavedTodoView;
use App\Models\Tag;
use App\Models\TimeEntry;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\TodoComment;
use App\Models\TodoDependency;
use App\Models\TodoRecurrenceException;
use App\Models\TodoRecurrenceRule;
use App\Models\TodoTemplate;
use App\Models\User;
use App\Queries\Todos\TodoCleanupQuery;
use App\Queries\Todos\TodoFocusQuery;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Hash;

test('database seeder creates safe demo users and complete private workspaces', function () {
    $this->seed(DatabaseSeeder::class);

    $users = User::query()->orderBy('email')->get();

    expect($users)->toHaveCount(2)
        ->and($users->pluck('email')->all())->toBe(['second@example.com', 'test@example.com'])
        ->and($users->firstWhere('email', 'test@example.com')->name)->toBe('Avery Chen')
        ->and($users->firstWhere('email', 'second@example.com')->name)->toBe('Morgan Blake')
        ->and($users->firstWhere('email', 'test@example.com')->is_admin)->toBeTrue()
        ->and($users->firstWhere('email', 'second@example.com')->is_admin)->toBeFalse()
        ->and(Hash::check('password', $users->firstWhere('email', 'test@example.com')->password))->toBeTrue()
        ->and(Project::query()->count())->toBeGreaterThanOrEqual(36)
        ->and(ProjectMembership::query()->count())->toBe(3)
        ->and(ProjectInvitation::query()->count())->toBe(4)
        ->and(AutomationRule::query()->count())->toBeGreaterThanOrEqual(20)
        ->and(AutomationRuleRun::query()->count())->toBeGreaterThanOrEqual(32)
        ->and(Goal::query()->count())->toBeGreaterThanOrEqual(32)
        ->and(GoalMilestone::query()->count())->toBeGreaterThanOrEqual(90)
        ->and(Habit::query()->count())->toBeGreaterThanOrEqual(28)
        ->and(HabitCheckIn::query()->count())->toBeGreaterThanOrEqual(252)
        ->and(PomodoroSession::query()->count())->toBeGreaterThanOrEqual(28)
        ->and(TimeEntry::query()->count())->toBeGreaterThanOrEqual(114)
        ->and(DatabaseNotification::query()->count())->toBeGreaterThanOrEqual(4)
        ->and(Reminder::query()->count())->toBeGreaterThanOrEqual(70)
        ->and(SavedTodoView::query()->count())->toBeGreaterThanOrEqual(38)
        ->and(Tag::query()->count())->toBeGreaterThanOrEqual(40)
        ->and(TodoChecklistItem::query()->count())->toBeGreaterThanOrEqual(450)
        ->and(TodoDependency::query()->count())->toBeGreaterThanOrEqual(46)
        ->and(TodoComment::withTrashed()->count())->toBeGreaterThanOrEqual(5)
        ->and(TodoRecurrenceException::query()->count())->toBeGreaterThanOrEqual(6)
        ->and(TodoRecurrenceRule::query()->count())->toBeGreaterThanOrEqual(4)
        ->and(TodoTemplate::query()->count())->toBeGreaterThanOrEqual(38)
        ->and(Todo::query()->count())->toBeGreaterThanOrEqual(174)
        ->and(Todo::withTrashed()->count())->toBeGreaterThanOrEqual(186);

    $users->each(function (User $user): void {
        $company = $user->email === 'test@example.com' ? 'Apple' : 'Microsoft';

        expect($user->projects()->whereNull('archived_at')->count())->toBeGreaterThanOrEqual(16)
            ->and($user->projects()->whereNotNull('archived_at')->count())->toBeGreaterThanOrEqual(2)
            ->and($user->automationRules()->count())->toBeGreaterThanOrEqual(10)
            ->and($user->automationRules()->pluck('name')->all())->toContain(
                'Archive completed routine tasks',
                'Promote overdue review tasks',
                "{$company} executive overdue escalation",
                "{$company} completed leadership archive",
            )
            ->and($user->automationRuleRuns()->count())->toBeGreaterThanOrEqual(16)
            ->and($user->goals()->count())->toBeGreaterThanOrEqual(16)
            ->and($user->goals()->pluck('title')->all())->toContain(
                'Launch the personal command center',
                'Plan a calmer weekend',
                "{$company} FY26 executive operating review",
            )
            ->and($user->goalMilestones()->count())->toBeGreaterThanOrEqual(45)
            ->and($user->goalMilestones()->whereNotNull('completed_at')->count())->toBeGreaterThanOrEqual(2)
            ->and($user->habits()->count())->toBeGreaterThanOrEqual(14)
            ->and($user->habits()->pluck('title')->all())->toContain('Plan the day', 'Run the weekly review')
            ->and($user->habitCheckIns()->count())->toBeGreaterThanOrEqual(126)
            ->and($user->pomodoroSessions()->count())->toBeGreaterThanOrEqual(14)
            ->and($user->timeEntries()->count())->toBeGreaterThanOrEqual(57)
            ->and($user->timeEntries()->sum('duration_seconds'))->toBeGreaterThanOrEqual(140000)
            ->and($user->notifications()->count())->toBeGreaterThanOrEqual(2)
            ->and($user->unreadNotifications()->count())->toBeGreaterThanOrEqual(1)
            ->and($user->readNotifications()->count())->toBeGreaterThanOrEqual(1)
            ->and($user->reminders()->count())->toBeGreaterThanOrEqual(35)
            ->and($user->reminders()->due()->count())->toBeGreaterThanOrEqual(3)
            ->and($user->reminders()->whereNotNull('skipped_at')->count())->toBeGreaterThanOrEqual(5)
            ->and($user->tags()->count())->toBeGreaterThanOrEqual(20)
            ->and($user->tags()->pluck('name')->all())->toContain('urgent', 'waiting')
            ->and($user->todos()->active()->count())->toBeGreaterThanOrEqual(70)
            ->and($user->todos()->completed()->count())->toBeGreaterThanOrEqual(9)
            ->and($user->todos()->archived()->count())->toBeGreaterThanOrEqual(7)
            ->and($user->todos()->onlyTrashed()->count())->toBeGreaterThanOrEqual(6)
            ->and($user->todos()->inInbox()->count())->toBeGreaterThanOrEqual(10)
            ->and($user->todoChecklistItems()->count())->toBeGreaterThanOrEqual(225)
            ->and($user->todoChecklistItems()->where('is_completed', true)->count())->toBeGreaterThanOrEqual(18)
            ->and($user->todoDependencies()->count())->toBeGreaterThanOrEqual(23)
            ->and($user->todoDependencies()->whereHas('todo', fn ($query) => $query->where('title', 'Send the overdue report'))->count())->toBe(1)
            ->and($user->todoRecurrenceExceptions()->count())->toBeGreaterThanOrEqual(3)
            ->and($user->todoRecurrenceRules()->count())->toBeGreaterThanOrEqual(2)
            ->and($user->todoTemplates()->count())->toBeGreaterThanOrEqual(19)
            ->and($user->todoTemplates()->pluck('name')->all())->toContain(
                'Bug triage checklist',
                'Daily planning routine',
                'Project kickoff',
                "{$company} executive decision memo",
            )
            ->and($user->todos()->overdue()->count())->toBeGreaterThanOrEqual(4)
            ->and($user->todos()->dueToday()->count())->toBeGreaterThanOrEqual(2)
            ->and($user->todos()->upcoming()->count())->toBeGreaterThanOrEqual(55)
            ->and($user->savedTodoViews()->count())->toBeGreaterThanOrEqual(19)
            ->and($user->savedTodoViews()->pluck('name')->all())->toContain(
                'Today focus',
                'Urgent work',
                'Waiting on others',
                'Executive future launch queue',
            )
            ->and(app(TodoFocusQuery::class)->for($user)->pluck('title')->all())
            ->toContain('Send the overdue report');

        $cleanupSummary = app(TodoCleanupQuery::class)->summaryFor($user);

        expect($cleanupSummary['stale'])->toBeGreaterThanOrEqual(1)
            ->and($cleanupSummary['unplanned'])->toBeGreaterThanOrEqual(1)
            ->and($cleanupSummary['blocked'])->toBeGreaterThanOrEqual(1)
            ->and($cleanupSummary['risky'])->toBeGreaterThanOrEqual(1);
    });
});

test('database seeder is idempotent for the current demo catalog', function () {
    $this->seed(DatabaseSeeder::class);

    $counts = databaseSeederCounts();

    $this->seed(DatabaseSeeder::class);

    expect(databaseSeederCounts())->toBe($counts)
        ->and(Todo::query()->where('title', 'Review the current flow')->count())->toBe(2);
});

test('database seeder does not create known demo credentials in production environment', function () {
    config(['app.env' => 'production']);

    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(0)
        ->and(Project::query()->count())->toBe(0)
        ->and(ProjectMembership::query()->count())->toBe(0)
        ->and(ProjectInvitation::query()->count())->toBe(0)
        ->and(AutomationRule::query()->count())->toBe(0)
        ->and(AutomationRuleRun::query()->count())->toBe(0)
        ->and(Goal::query()->count())->toBe(0)
        ->and(GoalMilestone::query()->count())->toBe(0)
        ->and(Habit::query()->count())->toBe(0)
        ->and(HabitCheckIn::query()->count())->toBe(0)
        ->and(PomodoroSession::query()->count())->toBe(0)
        ->and(TimeEntry::query()->count())->toBe(0)
        ->and(DatabaseNotification::query()->count())->toBe(0)
        ->and(Reminder::query()->count())->toBe(0)
        ->and(SavedTodoView::query()->count())->toBe(0)
        ->and(Tag::query()->count())->toBe(0)
        ->and(TodoChecklistItem::query()->count())->toBe(0)
        ->and(TodoDependency::query()->count())->toBe(0)
        ->and(TodoRecurrenceException::query()->count())->toBe(0)
        ->and(TodoRecurrenceRule::query()->count())->toBe(0)
        ->and(TodoTemplate::query()->count())->toBe(0)
        ->and(Todo::query()->count())->toBe(0);
});

/**
 * @return array<string, int>
 */
function databaseSeederCounts(): array
{
    return [
        'users' => User::query()->count(),
        'projects' => Project::query()->count(),
        'project_memberships' => ProjectMembership::query()->count(),
        'project_invitations' => ProjectInvitation::query()->count(),
        'automation_rules' => AutomationRule::query()->count(),
        'automation_runs' => AutomationRuleRun::query()->count(),
        'goals' => Goal::query()->count(),
        'goal_milestones' => GoalMilestone::query()->count(),
        'habits' => Habit::query()->count(),
        'habit_check_ins' => HabitCheckIn::query()->count(),
        'pomodoros' => PomodoroSession::query()->count(),
        'time_entries' => TimeEntry::query()->count(),
        'notifications' => DatabaseNotification::query()->count(),
        'reminders' => Reminder::query()->count(),
        'saved_views' => SavedTodoView::query()->count(),
        'tags' => Tag::query()->count(),
        'checklist_items' => TodoChecklistItem::query()->count(),
        'dependencies' => TodoDependency::query()->count(),
        'recurrence_exceptions' => TodoRecurrenceException::query()->count(),
        'recurrence_rules' => TodoRecurrenceRule::query()->count(),
        'templates' => TodoTemplate::query()->count(),
        'todos' => Todo::query()->count(),
        'todos_with_trashed' => Todo::withTrashed()->count(),
    ];
}

<?php

namespace App\Queries\Dashboard;

use App\Enums\ReminderStatus;
use App\Enums\TimeEntryStatus;
use App\Models\Goal;
use App\Models\GoalMilestone;
use App\Models\Habit;
use App\Models\HabitCheckIn;
use App\Models\Reminder;
use App\Models\TimeEntry;
use App\Models\TodoRecurrenceRule;
use App\Models\User;
use App\Queries\Projects\ProjectListQuery;
use App\Queries\Todos\TodoListQuery;

final class DashboardFoundationQuery
{
    public function __construct(
        private readonly TodoListQuery $todos,
        private readonly ProjectListQuery $projects,
    ) {}

    /**
     * Owner-scoped dashboard foundation counters.
     *
     * @return array{
     *     today: int,
     *     overdue: int,
     *     upcoming: int,
     *     priority_urgent: int,
     *     priority_high: int,
     *     priority_normal: int,
     *     priority_low: int,
     *     reminders_due: int,
     *     reminders_pending: int,
     *     recurrence_enabled: int,
     *     recurrence_paused: int,
     *     recurrence_generated: int,
     *     goals_open: int,
     *     goals_due_soon: int,
     *     milestones_open: int,
     *     habits_active: int,
     *     habits_checked_today: int,
     *     projects_active: int,
     *     projects_with_active_tasks: int,
     *     time_today_seconds: int,
     *     time_week_seconds: int,
     *     active_timers: int
     * }
     */
    public function for(User $user): array
    {
        $today = today()->toDateString();
        $weekStartsOn = today()->startOfWeek()->toDateString();
        $soonEndsOn = today()->addDays(7)->toDateString();

        $tasks = $this->todos->dashboardTaskCountsFor($user);

        $reminders = Reminder::query()
            ->ownedBy($user)
            ->whereHas('todo', fn ($todo) => $todo
                ->where('todos.user_id', $user->id)
                ->whereNull('todos.deleted_at')
                ->active())
            ->selectRaw('sum(case when status = ? and remind_at <= ? then 1 else 0 end) as due_count', [ReminderStatus::Pending->value, now()->toDateTimeString()])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as pending_count', [ReminderStatus::Pending->value])
            ->first();

        $recurrenceRules = TodoRecurrenceRule::query()
            ->ownedBy($user)
            ->whereHas('todo', fn ($todo) => $todo
                ->where('todos.user_id', $user->id)
                ->whereNull('todos.deleted_at')
                ->active())
            ->selectRaw('sum(case when is_enabled = 1 then 1 else 0 end) as enabled_count')
            ->selectRaw('sum(case when is_enabled = 0 then 1 else 0 end) as paused_count')
            ->first();

        $goals = Goal::query()
            ->ownedBy($user)
            ->active()
            ->selectRaw('sum(case when completed_at is null then 1 else 0 end) as open_count')
            ->selectRaw('sum(case when completed_at is null and target_date is not null and date(target_date) <= ? then 1 else 0 end) as due_soon_count', [$soonEndsOn])
            ->first();

        $habits = Habit::query()
            ->ownedBy($user)
            ->active()
            ->selectRaw('count(*) as active_count')
            ->first();

        $time = TimeEntry::query()
            ->ownedBy($user)
            ->selectRaw('sum(case when status = ? and date(entry_date) = ? then duration_seconds else 0 end) as today_seconds', [TimeEntryStatus::Completed->value, $today])
            ->selectRaw('sum(case when status = ? and date(entry_date) >= ? then duration_seconds else 0 end) as week_seconds', [TimeEntryStatus::Completed->value, $weekStartsOn])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as active_timer_count', [TimeEntryStatus::Running->value])
            ->first();

        return [
            'today' => $tasks['today'],
            'overdue' => $tasks['overdue'],
            'upcoming' => $tasks['due_soon'],
            'priority_urgent' => $tasks['priority_urgent'],
            'priority_high' => $tasks['priority_high'],
            'priority_normal' => $tasks['priority_normal'],
            'priority_low' => $tasks['priority_low'],
            'reminders_due' => (int) ($reminders->due_count ?? 0),
            'reminders_pending' => (int) ($reminders->pending_count ?? 0),
            'recurrence_enabled' => (int) ($recurrenceRules->enabled_count ?? 0),
            'recurrence_paused' => (int) ($recurrenceRules->paused_count ?? 0),
            'recurrence_generated' => $tasks['recurrence_generated'],
            'goals_open' => (int) ($goals->open_count ?? 0),
            'goals_due_soon' => (int) ($goals->due_soon_count ?? 0),
            'milestones_open' => GoalMilestone::query()
                ->ownedBy($user)
                ->whereHas('goal', fn ($goal) => $goal
                    ->where('goals.user_id', $user->id)
                    ->active()
                    ->whereNull('completed_at'))
                ->whereNull('completed_at')
                ->count(),
            'habits_active' => (int) ($habits->active_count ?? 0),
            'habits_checked_today' => HabitCheckIn::query()
                ->ownedBy($user)
                ->whereHas('habit', fn ($habit) => $habit
                    ->where('habits.user_id', $user->id)
                    ->active())
                ->whereDate('occurred_on', $today)
                ->count(),
            'projects_active' => $this->projects->activeAccessibleFor($user)->count(),
            'projects_with_active_tasks' => $tasks['projects_with_active_tasks'],
            'time_today_seconds' => (int) ($time->today_seconds ?? 0),
            'time_week_seconds' => (int) ($time->week_seconds ?? 0),
            'active_timers' => (int) ($time->active_timer_count ?? 0),
        ];
    }
}

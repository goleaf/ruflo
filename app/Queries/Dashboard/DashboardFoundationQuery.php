<?php

namespace App\Queries\Dashboard;

use App\Enums\Priority;
use App\Enums\ReminderStatus;
use App\Enums\TimeEntryStatus;
use App\Models\Goal;
use App\Models\GoalMilestone;
use App\Models\Habit;
use App\Models\HabitCheckIn;
use App\Models\Project;
use App\Models\Reminder;
use App\Models\TimeEntry;
use App\Models\Todo;
use App\Models\TodoRecurrenceRule;
use App\Models\User;

final class DashboardFoundationQuery
{
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

        $tasks = Todo::query()
            ->ownedBy($user)
            ->active()
            ->selectRaw('sum(case when date(due_date) = ? then 1 else 0 end) as today_count', [$today])
            ->selectRaw('sum(case when due_date is not null and date(due_date) < ? then 1 else 0 end) as overdue_count', [$today])
            ->selectRaw('sum(case when date(due_date) > ? and date(due_date) <= ? then 1 else 0 end) as upcoming_count', [$today, $soonEndsOn])
            ->selectRaw('sum(case when priority = ? then 1 else 0 end) as urgent_count', [Priority::Urgent->value])
            ->selectRaw('sum(case when priority = ? then 1 else 0 end) as high_count', [Priority::High->value])
            ->selectRaw('sum(case when priority = ? then 1 else 0 end) as normal_count', [Priority::Normal->value])
            ->selectRaw('sum(case when priority = ? then 1 else 0 end) as low_count', [Priority::Low->value])
            ->first();

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
            'today' => (int) ($tasks->today_count ?? 0),
            'overdue' => (int) ($tasks->overdue_count ?? 0),
            'upcoming' => (int) ($tasks->upcoming_count ?? 0),
            'priority_urgent' => (int) ($tasks->urgent_count ?? 0),
            'priority_high' => (int) ($tasks->high_count ?? 0),
            'priority_normal' => (int) ($tasks->normal_count ?? 0),
            'priority_low' => (int) ($tasks->low_count ?? 0),
            'reminders_due' => (int) ($reminders->due_count ?? 0),
            'reminders_pending' => (int) ($reminders->pending_count ?? 0),
            'recurrence_enabled' => (int) ($recurrenceRules->enabled_count ?? 0),
            'recurrence_paused' => (int) ($recurrenceRules->paused_count ?? 0),
            'recurrence_generated' => Todo::query()
                ->ownedBy($user)
                ->active()
                ->whereNotNull('recurrence_rule_id')
                ->whereNotNull('recurrence_occurs_on')
                ->count(),
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
            'projects_active' => Project::query()
                ->ownedBy($user)
                ->active()
                ->count(),
            'projects_with_active_tasks' => Todo::query()
                ->ownedBy($user)
                ->active()
                ->whereNotNull('project_id')
                ->whereHas('project', fn ($project) => $project
                    ->where('projects.user_id', $user->id)
                    ->active())
                ->distinct('project_id')
                ->count('project_id'),
            'time_today_seconds' => (int) ($time->today_seconds ?? 0),
            'time_week_seconds' => (int) ($time->week_seconds ?? 0),
            'active_timers' => (int) ($time->active_timer_count ?? 0),
        ];
    }
}

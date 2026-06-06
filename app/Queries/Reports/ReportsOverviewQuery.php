<?php

namespace App\Queries\Reports;

use App\Enums\Priority;
use App\Enums\TimeEntryStatus;
use App\Models\Habit;
use App\Models\HabitCheckIn;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class ReportsOverviewQuery
{
    /**
     * Build owner-scoped local report widgets without jobs, caches, or external services.
     *
     * @return array{
     *     generated_on: string,
     *     productivity: array{active: int, completed_this_week: int, completed_previous_week: int, completion_delta: int, due_today: int, due_next_7_days: int, inbox: int, completion_percent: int},
     *     overdue: array{total: int, high_priority: int, urgent_priority: int, oldest_age_days: int, one_to_three_days: int, four_to_seven_days: int, eight_plus_days: int},
     *     habits: array{active: int, checked_today: int, check_ins_this_week: int, check_ins_previous_week: int, weekly_delta: int, weekly_distinct_habits: int, adherence_percent: int},
     *     projects: array{active: int, with_active_tasks: int, completed_tasks_this_week: int, overdue_tasks: int, no_project_active: int, top: list<array{id: int, name: string, color: string, active: int, completed: int, overdue: int, completion_percent: int}>},
     *     time: array{today_seconds: int, week_seconds: int, previous_week_seconds: int, delta_seconds: int, active_timers: int},
     *     charts: array{
     *         productivity: list<array{key: string, label: string, value: int, percent: int, summary: string}>,
     *         overdue: list<array{key: string, label: string, value: int, percent: int, summary: string}>,
     *         habits: list<array{key: string, label: string, value: int, percent: int, summary: string}>,
     *         time: list<array{key: string, label: string, value: int, percent: int, summary: string}>
     *     }
     * }
     */
    public function for(User $user): array
    {
        $today = today()->toDateString();
        $soonEndsOn = today()->addDays(7)->toDateString();
        $weekStartsOn = today()->startOfWeek()->toDateString();
        $previousWeekStartsOn = today()->subWeek()->startOfWeek()->toDateString();
        $previousWeekEndsOn = today()->subWeek()->endOfWeek()->toDateString();
        $threeDaysAgo = today()->subDays(3)->toDateString();
        $sevenDaysAgo = today()->subDays(7)->toDateString();

        $productivity = $this->productivity($user, $today, $soonEndsOn, $weekStartsOn, $previousWeekStartsOn, $previousWeekEndsOn);
        $overdue = $this->overdue($user, $today, $threeDaysAgo, $sevenDaysAgo);
        $habits = $this->habits($user, $today, $weekStartsOn, $previousWeekStartsOn, $previousWeekEndsOn);
        $projects = $this->projects($user, $today, $weekStartsOn);
        $time = $this->time($user, $today, $weekStartsOn, $previousWeekStartsOn, $previousWeekEndsOn);

        return [
            'generated_on' => $today,
            'productivity' => $productivity,
            'overdue' => $overdue,
            'habits' => $habits,
            'projects' => $projects,
            'time' => $time,
            'charts' => [
                'productivity' => $this->chart([
                    ['key' => 'active', 'label' => __('reports.charts.productivity.active'), 'value' => $productivity['active']],
                    ['key' => 'completed', 'label' => __('reports.charts.productivity.completed'), 'value' => $productivity['completed_this_week']],
                    ['key' => 'due_today', 'label' => __('reports.charts.productivity.due_today'), 'value' => $productivity['due_today']],
                    ['key' => 'due_next_7_days', 'label' => __('reports.charts.productivity.due_next_7_days'), 'value' => $productivity['due_next_7_days']],
                ], 'reports.charts.item_summary'),
                'overdue' => $this->chart([
                    ['key' => 'one_to_three_days', 'label' => __('reports.charts.overdue.one_to_three_days'), 'value' => $overdue['one_to_three_days']],
                    ['key' => 'four_to_seven_days', 'label' => __('reports.charts.overdue.four_to_seven_days'), 'value' => $overdue['four_to_seven_days']],
                    ['key' => 'eight_plus_days', 'label' => __('reports.charts.overdue.eight_plus_days'), 'value' => $overdue['eight_plus_days']],
                ], 'reports.charts.item_summary'),
                'habits' => $this->chart([
                    ['key' => 'active', 'label' => __('reports.charts.habits.active'), 'value' => $habits['active']],
                    ['key' => 'checked_today', 'label' => __('reports.charts.habits.checked_today'), 'value' => $habits['checked_today']],
                    ['key' => 'this_week', 'label' => __('reports.charts.habits.this_week'), 'value' => $habits['check_ins_this_week']],
                    ['key' => 'distinct', 'label' => __('reports.charts.habits.distinct'), 'value' => $habits['weekly_distinct_habits']],
                ], 'reports.charts.item_summary'),
                'time' => $this->chart([
                    ['key' => 'today', 'label' => __('reports.charts.time.today'), 'value' => intdiv($time['today_seconds'], 60)],
                    ['key' => 'this_week', 'label' => __('reports.charts.time.this_week'), 'value' => intdiv($time['week_seconds'], 60)],
                    ['key' => 'previous_week', 'label' => __('reports.charts.time.previous_week'), 'value' => intdiv($time['previous_week_seconds'], 60)],
                ], 'reports.charts.minutes_summary'),
            ],
        ];
    }

    /**
     * @return array{active: int, completed_this_week: int, completed_previous_week: int, completion_delta: int, due_today: int, due_next_7_days: int, inbox: int, completion_percent: int}
     */
    private function productivity(User $user, string $today, string $soonEndsOn, string $weekStartsOn, string $previousWeekStartsOn, string $previousWeekEndsOn): array
    {
        $activeTasks = Todo::query()
            ->ownedBy($user)
            ->active()
            ->selectRaw('count(*) as active_count')
            ->selectRaw('sum(case when date(due_date) = ? then 1 else 0 end) as due_today_count', [$today])
            ->selectRaw('sum(case when date(due_date) > ? and date(due_date) <= ? then 1 else 0 end) as due_next_7_days_count', [$today, $soonEndsOn])
            ->selectRaw('sum(case when inbox_captured_at is not null then 1 else 0 end) as inbox_count')
            ->first();

        $completedTasks = Todo::query()
            ->ownedBy($user)
            ->completed()
            ->selectRaw('sum(case when date(updated_at) >= ? then 1 else 0 end) as completed_this_week_count', [$weekStartsOn])
            ->selectRaw('sum(case when date(updated_at) >= ? and date(updated_at) <= ? then 1 else 0 end) as completed_previous_week_count', [$previousWeekStartsOn, $previousWeekEndsOn])
            ->first();

        $active = (int) ($activeTasks->active_count ?? 0);
        $completedThisWeek = (int) ($completedTasks->completed_this_week_count ?? 0);
        $completedPreviousWeek = (int) ($completedTasks->completed_previous_week_count ?? 0);
        $knownTasks = $active + $completedThisWeek;

        return [
            'active' => $active,
            'completed_this_week' => $completedThisWeek,
            'completed_previous_week' => $completedPreviousWeek,
            'completion_delta' => $completedThisWeek - $completedPreviousWeek,
            'due_today' => (int) ($activeTasks->due_today_count ?? 0),
            'due_next_7_days' => (int) ($activeTasks->due_next_7_days_count ?? 0),
            'inbox' => (int) ($activeTasks->inbox_count ?? 0),
            'completion_percent' => $knownTasks > 0 ? (int) round(($completedThisWeek / $knownTasks) * 100) : 0,
        ];
    }

    /**
     * @return array{total: int, high_priority: int, urgent_priority: int, oldest_age_days: int, one_to_three_days: int, four_to_seven_days: int, eight_plus_days: int}
     */
    private function overdue(User $user, string $today, string $threeDaysAgo, string $sevenDaysAgo): array
    {
        $oldestDueDate = Todo::query()
            ->ownedBy($user)
            ->active()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->min('due_date');

        $oldestDueDate = is_string($oldestDueDate) ? $oldestDueDate : null;

        $buckets = Todo::query()
            ->ownedBy($user)
            ->active()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->selectRaw('count(*) as total_count')
            ->selectRaw('sum(case when priority = ? then 1 else 0 end) as high_count', [Priority::High->value])
            ->selectRaw('sum(case when priority = ? then 1 else 0 end) as urgent_count', [Priority::Urgent->value])
            ->selectRaw('sum(case when date(due_date) >= ? then 1 else 0 end) as one_to_three_count', [$threeDaysAgo])
            ->selectRaw('sum(case when date(due_date) >= ? and date(due_date) < ? then 1 else 0 end) as four_to_seven_count', [$sevenDaysAgo, $threeDaysAgo])
            ->selectRaw('sum(case when date(due_date) < ? then 1 else 0 end) as eight_plus_count', [$sevenDaysAgo])
            ->first();

        return [
            'total' => (int) ($buckets->total_count ?? 0),
            'high_priority' => (int) ($buckets->high_count ?? 0),
            'urgent_priority' => (int) ($buckets->urgent_count ?? 0),
            'oldest_age_days' => $oldestDueDate !== null ? abs((int) today()->diffInDays($oldestDueDate, false)) : 0,
            'one_to_three_days' => (int) ($buckets->one_to_three_count ?? 0),
            'four_to_seven_days' => (int) ($buckets->four_to_seven_count ?? 0),
            'eight_plus_days' => (int) ($buckets->eight_plus_count ?? 0),
        ];
    }

    /**
     * @return array{active: int, checked_today: int, check_ins_this_week: int, check_ins_previous_week: int, weekly_delta: int, weekly_distinct_habits: int, adherence_percent: int}
     */
    private function habits(User $user, string $today, string $weekStartsOn, string $previousWeekStartsOn, string $previousWeekEndsOn): array
    {
        $activeHabits = Habit::query()
            ->ownedBy($user)
            ->active()
            ->count();

        $checkIns = HabitCheckIn::query()
            ->ownedBy($user)
            ->whereHas('habit', fn (Builder $habit): Builder => $habit
                ->where('habits.user_id', $user->id)
                ->active())
            ->selectRaw('sum(case when date(occurred_on) = ? then 1 else 0 end) as checked_today_count', [$today])
            ->selectRaw('sum(case when date(occurred_on) >= ? then 1 else 0 end) as this_week_count', [$weekStartsOn])
            ->selectRaw('sum(case when date(occurred_on) >= ? and date(occurred_on) <= ? then 1 else 0 end) as previous_week_count', [$previousWeekStartsOn, $previousWeekEndsOn])
            ->first();

        $weeklyDistinctHabits = HabitCheckIn::query()
            ->ownedBy($user)
            ->whereHas('habit', fn (Builder $habit): Builder => $habit
                ->where('habits.user_id', $user->id)
                ->active())
            ->whereDate('occurred_on', '>=', $weekStartsOn)
            ->distinct()
            ->count('habit_id');

        $checkInsThisWeek = (int) ($checkIns->this_week_count ?? 0);
        $checkInsPreviousWeek = (int) ($checkIns->previous_week_count ?? 0);

        return [
            'active' => $activeHabits,
            'checked_today' => (int) ($checkIns->checked_today_count ?? 0),
            'check_ins_this_week' => $checkInsThisWeek,
            'check_ins_previous_week' => $checkInsPreviousWeek,
            'weekly_delta' => $checkInsThisWeek - $checkInsPreviousWeek,
            'weekly_distinct_habits' => $weeklyDistinctHabits,
            'adherence_percent' => $activeHabits > 0 ? (int) round(($weeklyDistinctHabits / $activeHabits) * 100) : 0,
        ];
    }

    /**
     * @return array{active: int, with_active_tasks: int, completed_tasks_this_week: int, overdue_tasks: int, no_project_active: int, top: list<array{id: int, name: string, color: string, active: int, completed: int, overdue: int, completion_percent: int}>}
     */
    private function projects(User $user, string $today, string $weekStartsOn): array
    {
        /** @var list<array{id: int, name: string, color: string, active: int, completed: int, overdue: int, completion_percent: int}> $topProjects */
        $topProjects = Project::query()
            ->ownedBy($user)
            ->active()
            ->select(['id', 'user_id', 'name', 'color'])
            ->withCount([
                'todos as active_count' => fn (Builder $todos): Builder => $this->ownedProjectTasks($todos, $user)->active(),
                'todos as completed_count' => fn (Builder $todos): Builder => $this->ownedProjectTasks($todos, $user)->completed(),
                'todos as overdue_count' => fn (Builder $todos): Builder => $this->ownedProjectTasks($todos, $user)->overdue(),
            ])
            ->orderByDesc('overdue_count')
            ->orderByDesc('active_count')
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(function (Project $project): array {
                $active = (int) $project->active_count;
                $completed = (int) $project->completed_count;
                $total = $active + $completed;

                return [
                    'id' => (int) $project->id,
                    'name' => $project->name,
                    'color' => $project->color,
                    'active' => $active,
                    'completed' => $completed,
                    'overdue' => (int) $project->overdue_count,
                    'completion_percent' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
                ];
            })
            ->values()
            ->all();

        return [
            'active' => Project::query()
                ->ownedBy($user)
                ->active()
                ->count(),
            'with_active_tasks' => Todo::query()
                ->ownedBy($user)
                ->active()
                ->whereNotNull('project_id')
                ->whereHas('project', fn (Builder $project): Builder => $project
                    ->where('projects.user_id', $user->id)
                    ->active())
                ->distinct('project_id')
                ->count('project_id'),
            'completed_tasks_this_week' => Todo::query()
                ->ownedBy($user)
                ->completed()
                ->whereDate('updated_at', '>=', $weekStartsOn)
                ->whereNotNull('project_id')
                ->whereHas('project', fn (Builder $project): Builder => $project
                    ->where('projects.user_id', $user->id)
                    ->active())
                ->count(),
            'overdue_tasks' => Todo::query()
                ->ownedBy($user)
                ->active()
                ->whereDate('due_date', '<', $today)
                ->whereNotNull('project_id')
                ->whereHas('project', fn (Builder $project): Builder => $project
                    ->where('projects.user_id', $user->id)
                    ->active())
                ->count(),
            'no_project_active' => Todo::query()
                ->ownedBy($user)
                ->active()
                ->whereNull('project_id')
                ->count(),
            'top' => $topProjects,
        ];
    }

    /**
     * @return array{today_seconds: int, week_seconds: int, previous_week_seconds: int, delta_seconds: int, active_timers: int}
     */
    private function time(User $user, string $today, string $weekStartsOn, string $previousWeekStartsOn, string $previousWeekEndsOn): array
    {
        $time = TimeEntry::query()
            ->ownedBy($user)
            ->selectRaw('sum(case when status = ? and date(entry_date) = ? then duration_seconds else 0 end) as today_seconds', [TimeEntryStatus::Completed->value, $today])
            ->selectRaw('sum(case when status = ? and date(entry_date) >= ? then duration_seconds else 0 end) as week_seconds', [TimeEntryStatus::Completed->value, $weekStartsOn])
            ->selectRaw('sum(case when status = ? and date(entry_date) >= ? and date(entry_date) <= ? then duration_seconds else 0 end) as previous_week_seconds', [TimeEntryStatus::Completed->value, $previousWeekStartsOn, $previousWeekEndsOn])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as active_timer_count', [TimeEntryStatus::Running->value])
            ->first();

        $weekSeconds = (int) ($time->week_seconds ?? 0);
        $previousWeekSeconds = (int) ($time->previous_week_seconds ?? 0);

        return [
            'today_seconds' => (int) ($time->today_seconds ?? 0),
            'week_seconds' => $weekSeconds,
            'previous_week_seconds' => $previousWeekSeconds,
            'delta_seconds' => $weekSeconds - $previousWeekSeconds,
            'active_timers' => (int) ($time->active_timer_count ?? 0),
        ];
    }

    /**
     * @param  Builder<Todo>  $query
     * @return Builder<Todo>
     */
    private function ownedProjectTasks(Builder $query, User $user): Builder
    {
        return $query->where('todos.user_id', $user->id);
    }

    /**
     * @param  list<array{key: string, label: string, value: int}>  $items
     * @return list<array{key: string, label: string, value: int, percent: int, summary: string}>
     */
    private function chart(array $items, string $summaryKey): array
    {
        $max = max(1, ...array_map(fn (array $item): int => $item['value'], $items));

        return array_map(fn (array $item): array => [
            'key' => $item['key'],
            'label' => $item['label'],
            'value' => $item['value'],
            'percent' => (int) max(4, round(($item['value'] / $max) * 100)),
            'summary' => __($summaryKey, [
                'label' => $item['label'],
                'value' => $item['value'],
            ]),
        ], $items);
    }
}

<?php

namespace App\Queries\Todos;

use App\Enums\Priority;
use App\Models\Todo;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

final class TodoCalendarQuery
{
    /**
     * @return array{
     *     month: CarbonImmutable,
     *     starts_at: CarbonImmutable,
     *     ends_at: CarbonImmutable,
     *     weeks: list<list<array{
     *         key: string,
     *         date: CarbonImmutable,
     *         day_number: string,
     *         weekday: string,
     *         in_month: bool,
     *         is_today: bool,
     *         todos: EloquentCollection<int, Todo>
     *     }>>,
     *     summary: array{month: int, overdue: int, today: int, upcoming: int, no_due_date: int},
     *     no_due_tasks: EloquentCollection<int, Todo>
     * }
     */
    public function monthFor(User $user, CarbonImmutable $month): array
    {
        $month = $month->startOfMonth();
        $monthStart = $month->startOfMonth();
        $monthEnd = $month->endOfMonth();
        $tasksByDate = $this->dueTasksForMonth($user, $monthStart, $monthEnd)
            ->groupBy(fn (Todo $todo): string => $todo->due_date?->toDateString() ?? '');

        return [
            'month' => $month,
            'starts_at' => $monthStart,
            'ends_at' => $monthEnd,
            'weeks' => $this->weeks($monthStart, $monthEnd, $tasksByDate),
            'summary' => $this->summaryFor($user, $monthStart, $monthEnd),
            'no_due_tasks' => $this->noDueTasksFor($user),
        ];
    }

    /**
     * @return EloquentCollection<int, Todo>
     */
    private function dueTasksForMonth(User $user, CarbonImmutable $monthStart, CarbonImmutable $monthEnd): EloquentCollection
    {
        return $this->withWorkspaceRelations(
            Todo::query()
                ->select(['id', 'user_id', 'project_id', 'title', 'priority', 'due_date', 'is_completed', 'archived_at', 'deleted_at', 'created_at', 'updated_at'])
                ->ownedBy($user)
                ->active()
                ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()]),
            $user,
        )
            ->orderBy('due_date')
            ->orderByRaw(Priority::sortCaseSql().' desc')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return EloquentCollection<int, Todo>
     */
    private function noDueTasksFor(User $user): EloquentCollection
    {
        return $this->withWorkspaceRelations(
            Todo::query()
                ->select(['id', 'user_id', 'project_id', 'title', 'priority', 'due_date', 'is_completed', 'archived_at', 'deleted_at', 'created_at', 'updated_at'])
                ->ownedBy($user)
                ->active()
                ->whereNull('due_date'),
            $user,
        )
            ->orderByRaw(Priority::sortCaseSql().' desc')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();
    }

    /**
     * @return array{month: int, overdue: int, today: int, upcoming: int, no_due_date: int}
     */
    private function summaryFor(User $user, CarbonImmutable $monthStart, CarbonImmutable $monthEnd): array
    {
        $today = today()->toDateString();
        $summary = Todo::query()
            ->ownedBy($user)
            ->active()
            ->selectRaw('sum(case when due_date between ? and ? then 1 else 0 end) as month_count', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->selectRaw('sum(case when due_date is not null and due_date < ? then 1 else 0 end) as overdue_count', [$today])
            ->selectRaw('sum(case when due_date = ? then 1 else 0 end) as today_count', [$today])
            ->selectRaw('sum(case when due_date is not null and due_date > ? then 1 else 0 end) as upcoming_count', [$today])
            ->selectRaw('sum(case when due_date is null then 1 else 0 end) as no_due_date_count')
            ->first();

        return [
            'month' => (int) $summary->month_count,
            'overdue' => (int) $summary->overdue_count,
            'today' => (int) $summary->today_count,
            'upcoming' => (int) $summary->upcoming_count,
            'no_due_date' => (int) $summary->no_due_date_count,
        ];
    }

    /**
     * @param  Collection<string, EloquentCollection<int, Todo>>  $tasksByDate
     * @return list<list<array{
     *     key: string,
     *     date: CarbonImmutable,
     *     day_number: string,
     *     weekday: string,
     *     in_month: bool,
     *     is_today: bool,
     *     todos: EloquentCollection<int, Todo>
     * }>>
     */
    private function weeks(CarbonImmutable $monthStart, CarbonImmutable $monthEnd, Collection $tasksByDate): array
    {
        $cursor = $monthStart->startOfWeek();
        $calendarEnd = $monthEnd->endOfWeek();
        $weeks = [];

        while ($cursor->lessThanOrEqualTo($calendarEnd)) {
            $week = [];

            for ($day = 0; $day < 7; $day++) {
                $key = $cursor->toDateString();

                $week[] = [
                    'key' => $key,
                    'date' => $cursor,
                    'day_number' => $cursor->isoFormat('D'),
                    'weekday' => $cursor->isoFormat('ddd'),
                    'in_month' => $cursor->betweenIncluded($monthStart, $monthEnd),
                    'is_today' => $cursor->isSameDay(today()),
                    'todos' => $tasksByDate->get($key, new EloquentCollection),
                ];

                $cursor = $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return $weeks;
    }

    /**
     * @param  Builder<Todo>  $query
     * @return Builder<Todo>
     */
    private function withWorkspaceRelations(Builder $query, User $user): Builder
    {
        return $query->with([
            'project' => fn (BelongsTo $project): BelongsTo => $project->where('projects.user_id', $user->id),
            'tags' => fn (BelongsToMany $tags): BelongsToMany => $tags->where('tags.user_id', $user->id),
        ]);
    }
}

<?php

namespace App\Queries\Dashboard;

use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class ProjectProgressDashboardQuery
{
    /**
     * Owner-scoped project progress signals for the dashboard.
     *
     * @return array{
     *     generated_on: string,
     *     projects: list<array{
     *         id: int,
     *         name: string,
     *         color: string,
     *         active: int,
     *         completed: int,
     *         overdue: int,
     *         due_soon: int,
     *         undated: int,
     *         stale: int,
     *         total: int,
     *         attention: int,
     *         completion_percent: int
     *     }>,
     *     no_project: array{
     *         active: int,
     *         overdue: int,
     *         due_soon: int,
     *         undated: int,
     *         stale: int,
     *         attention: int
     *     },
     *     totals: array{
     *         active_projects: int,
     *         archived_projects: int,
     *         displayed_projects: int,
     *         active_tasks: int,
     *         completed_tasks: int,
     *         total_tasks: int,
     *         completion_percent: int,
     *         overdue: int,
     *         due_soon: int,
     *         undated: int,
     *         stale: int,
     *         no_project_active: int,
     *         cleanup_signals: int
     *     }
     * }
     */
    public function for(User $user): array
    {
        $today = today()->toDateString();
        $soonEndsOn = today()->addDays(7)->toDateString();
        $staleBefore = now()->subDays(14)->toDateTimeString();

        /** @var list<array{id: int, name: string, color: string, active: int, completed: int, overdue: int, due_soon: int, undated: int, stale: int, total: int, attention: int, completion_percent: int}> $projects */
        $projects = Project::query()
            ->ownedBy($user)
            ->active()
            ->select(['id', 'user_id', 'name', 'color'])
            ->withCount([
                'todos as active_count' => fn (Builder $query): Builder => $this->ownedTasks($query, $user)->active(),
                'todos as completed_count' => fn (Builder $query): Builder => $this->ownedTasks($query, $user)->completed(),
                'todos as overdue_count' => fn (Builder $query): Builder => $this->ownedTasks($query, $user)->overdue(),
                'todos as due_soon_count' => fn (Builder $query): Builder => $this->ownedTasks($query, $user)
                    ->active()
                    ->whereDate('due_date', '>=', $today)
                    ->whereDate('due_date', '<=', $soonEndsOn),
                'todos as undated_count' => fn (Builder $query): Builder => $this->ownedTasks($query, $user)
                    ->active()
                    ->whereNull('due_date'),
                'todos as stale_count' => fn (Builder $query): Builder => $this->ownedTasks($query, $user)
                    ->active()
                    ->where('updated_at', '<=', $staleBefore),
            ])
            ->orderByDesc('overdue_count')
            ->orderByDesc('undated_count')
            ->orderByDesc('stale_count')
            ->orderByDesc('active_count')
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(function (Project $project): array {
                $active = (int) $project->active_count;
                $completed = (int) $project->completed_count;
                $overdue = (int) $project->overdue_count;
                $dueSoon = (int) $project->due_soon_count;
                $undated = (int) $project->undated_count;
                $stale = (int) $project->stale_count;
                $total = $active + $completed;
                $attention = $overdue + $undated + $stale;

                return [
                    'id' => (int) $project->id,
                    'name' => $project->name,
                    'color' => $project->color,
                    'active' => $active,
                    'completed' => $completed,
                    'overdue' => $overdue,
                    'due_soon' => $dueSoon,
                    'undated' => $undated,
                    'stale' => $stale,
                    'total' => $total,
                    'attention' => $attention,
                    'completion_percent' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
                ];
            })
            ->values()
            ->all();

        $noProject = $this->aggregateNoProjectTasks($user, $today, $soonEndsOn, $staleBefore);
        $totals = $this->aggregateTotals($user, $today, $soonEndsOn, $staleBefore, count($projects), $noProject);

        return [
            'generated_on' => $today,
            'projects' => $projects,
            'no_project' => $noProject,
            'totals' => $totals,
        ];
    }

    /**
     * @param  Builder<Todo>  $query
     * @return Builder<Todo>
     */
    private function ownedTasks(Builder $query, User $user): Builder
    {
        return $query->where('todos.user_id', $user->id);
    }

    /**
     * @return array{active: int, overdue: int, due_soon: int, undated: int, stale: int, attention: int}
     */
    private function aggregateNoProjectTasks(User $user, string $today, string $soonEndsOn, string $staleBefore): array
    {
        $tasks = Todo::query()
            ->ownedBy($user)
            ->active()
            ->whereNull('project_id')
            ->selectRaw('count(*) as active_count')
            ->selectRaw('sum(case when due_date is not null and date(due_date) < ? then 1 else 0 end) as overdue_count', [$today])
            ->selectRaw('sum(case when date(due_date) >= ? and date(due_date) <= ? then 1 else 0 end) as due_soon_count', [$today, $soonEndsOn])
            ->selectRaw('sum(case when due_date is null then 1 else 0 end) as undated_count')
            ->selectRaw('sum(case when updated_at <= ? then 1 else 0 end) as stale_count', [$staleBefore])
            ->first();

        $overdue = (int) ($tasks->overdue_count ?? 0);
        $undated = (int) ($tasks->undated_count ?? 0);
        $stale = (int) ($tasks->stale_count ?? 0);

        return [
            'active' => (int) ($tasks->active_count ?? 0),
            'overdue' => $overdue,
            'due_soon' => (int) ($tasks->due_soon_count ?? 0),
            'undated' => $undated,
            'stale' => $stale,
            'attention' => $overdue + $undated + $stale,
        ];
    }

    /**
     * @param  array{active: int, overdue: int, due_soon: int, undated: int, stale: int, attention: int}  $noProject
     * @return array{active_projects: int, archived_projects: int, displayed_projects: int, active_tasks: int, completed_tasks: int, total_tasks: int, completion_percent: int, overdue: int, due_soon: int, undated: int, stale: int, no_project_active: int, cleanup_signals: int}
     */
    private function aggregateTotals(User $user, string $today, string $soonEndsOn, string $staleBefore, int $displayedProjects, array $noProject): array
    {
        $activeProjectTasks = $this->activeProjectTaskBase($user)
            ->selectRaw('sum(case when archived_at is null and is_completed = 0 then 1 else 0 end) as active_count')
            ->selectRaw('sum(case when archived_at is null and is_completed = 1 then 1 else 0 end) as completed_count')
            ->selectRaw('sum(case when archived_at is null and is_completed = 0 and due_date is not null and date(due_date) < ? then 1 else 0 end) as overdue_count', [$today])
            ->selectRaw('sum(case when archived_at is null and is_completed = 0 and date(due_date) >= ? and date(due_date) <= ? then 1 else 0 end) as due_soon_count', [$today, $soonEndsOn])
            ->selectRaw('sum(case when archived_at is null and is_completed = 0 and due_date is null then 1 else 0 end) as undated_count')
            ->selectRaw('sum(case when archived_at is null and is_completed = 0 and updated_at <= ? then 1 else 0 end) as stale_count', [$staleBefore])
            ->first();

        $overdue = (int) ($activeProjectTasks->overdue_count ?? 0);
        $undated = (int) ($activeProjectTasks->undated_count ?? 0);
        $stale = (int) ($activeProjectTasks->stale_count ?? 0);
        $active = (int) ($activeProjectTasks->active_count ?? 0);
        $completed = (int) ($activeProjectTasks->completed_count ?? 0);
        $total = $active + $completed;

        return [
            'active_projects' => Project::query()
                ->ownedBy($user)
                ->active()
                ->count(),
            'archived_projects' => Project::query()
                ->ownedBy($user)
                ->archived()
                ->count(),
            'displayed_projects' => $displayedProjects,
            'active_tasks' => $active,
            'completed_tasks' => $completed,
            'total_tasks' => $total,
            'completion_percent' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
            'overdue' => $overdue,
            'due_soon' => (int) ($activeProjectTasks->due_soon_count ?? 0),
            'undated' => $undated,
            'stale' => $stale,
            'no_project_active' => $noProject['active'],
            'cleanup_signals' => $overdue + $undated + $stale + $noProject['attention'],
        ];
    }

    /**
     * @return Builder<Todo>
     */
    private function activeProjectTaskBase(User $user): Builder
    {
        return Todo::query()
            ->ownedBy($user)
            ->whereNotNull('project_id')
            ->whereHas('project', fn (Builder $project): Builder => $project
                ->where('projects.user_id', $user->id)
                ->active());
    }
}

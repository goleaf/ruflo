<?php

namespace App\Queries\Dashboard;

use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use App\Queries\Todos\TodoListQuery;

final class DailySummaryQuery
{
    public function __construct(
        private readonly TodoListQuery $todos,
    ) {}

    /**
     * Current user's private workspace summary for dashboard counters.
     *
     * @return array{active: int, overdue: int, completed: int, archived: int, projects: int, tags: int}
     */
    public function for(User $user): array
    {
        $todoSummary = $this->todos->summaryFor($user);

        return [
            ...$todoSummary,
            'projects' => Project::query()->ownedBy($user)->active()->count(),
            'tags' => Tag::query()->ownedBy($user)->count(),
        ];
    }
}

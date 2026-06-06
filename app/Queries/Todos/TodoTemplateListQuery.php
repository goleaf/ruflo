<?php

namespace App\Queries\Todos;

use App\Models\TodoTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Owner-scoped read boundary for reusable task templates.
 */
final class TodoTemplateListQuery
{
    /**
     * @return Collection<int, TodoTemplate>
     */
    public function for(User $user): Collection
    {
        return TodoTemplate::query()
            ->ownedBy($user)
            ->orderByDesc('updated_at')
            ->orderBy('name')
            ->get();
    }

    public function findFor(User $user, int $templateId): TodoTemplate
    {
        return TodoTemplate::query()
            ->ownedBy($user)
            ->findOrFail($templateId);
    }
}

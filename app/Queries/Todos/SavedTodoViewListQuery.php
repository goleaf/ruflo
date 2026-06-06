<?php

namespace App\Queries\Todos;

use App\Models\SavedTodoView;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Owner-scoped read boundary for saved task views.
 */
final class SavedTodoViewListQuery
{
    /**
     * @return Collection<int, SavedTodoView>
     */
    public function for(User $user): Collection
    {
        return SavedTodoView::query()
            ->ownedBy($user)
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    public function findFor(User $user, int $savedViewId): SavedTodoView
    {
        return SavedTodoView::query()
            ->ownedBy($user)
            ->findOrFail($savedViewId);
    }
}

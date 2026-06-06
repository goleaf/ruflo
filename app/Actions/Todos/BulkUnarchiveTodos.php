<?php

namespace App\Actions\Todos;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Unarchives the user's archived tasks among the selected ids.
 *
 * Foreign ids and non-archived ids are excluded before authorization and
 * mutation. Completion state is preserved, matching single-task unarchive.
 */
final class BulkUnarchiveTodos
{
    public function __construct(
        private readonly UnarchiveTodo $unarchiveTodo,
    ) {}

    /**
     * @param  list<int>  $ids
     */
    public function handle(User $user, array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        $todos = $user->todos()
            ->archived()
            ->whereKey($ids)
            ->get(['id', 'user_id', 'is_completed', 'archived_at']);

        $todos->each(fn (Todo $todo) => Gate::forUser($user)->authorize('unarchive', $todo));

        $todos->each(fn (Todo $todo) => $this->unarchiveTodo->handle($todo));

        return $todos->count();
    }
}

<?php

namespace App\Actions\Todos;

use App\Data\Todos\BulkActionResult;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Completes the user's active tasks among the selected ids.
 *
 * The selection is re-scoped to the user's own active tasks, so foreign or
 * non-actionable ids in the payload are silently excluded — a bulk action can
 * never touch another user's data or complete an archived task. Returns the
 * result with selected, changed, skipped, and failed counts.
 *
 * @param  list<int>  $ids
 */
final class BulkCompleteTodos
{
    public function __construct(
        private readonly CompleteTodo $completeTodo,
    ) {}

    /**
     * @param  list<int>  $ids
     */
    public function handle(User $user, array $ids): BulkActionResult
    {
        if ($ids === []) {
            return BulkActionResult::fromIds([], affected: 0);
        }

        $todos = $user->todos()
            ->active()
            ->whereKey($ids)
            ->get(['id', 'user_id', 'is_completed', 'archived_at']);

        $todos->each(fn (Todo $todo) => Gate::forUser($user)->authorize('complete', $todo));

        $todos->each(fn (Todo $todo) => $this->completeTodo->handle($todo));

        return BulkActionResult::fromIds($ids, affected: $todos->count());
    }
}

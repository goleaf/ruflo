<?php

namespace App\Actions\Todos;

use App\Data\Todos\BulkActionResult;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Archives the user's non-archived tasks among the selected ids.
 *
 * Re-scoped to the user's own tasks so foreign or already-archived ids are
 * silently excluded. Returns selected, archived, skipped, and failed counts.
 */
final class BulkArchiveTodos
{
    public function __construct(
        private readonly ArchiveTodo $archiveTodo,
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
            ->whereNull('archived_at')
            ->whereKey($ids)
            ->get(['id', 'user_id', 'archived_at']);

        $todos->each(fn (Todo $todo) => Gate::forUser($user)->authorize('archive', $todo));

        $todos->each(fn (Todo $todo) => $this->archiveTodo->handle($todo));

        return BulkActionResult::fromIds($ids, affected: $todos->count());
    }
}

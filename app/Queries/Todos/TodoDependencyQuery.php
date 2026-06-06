<?php

namespace App\Queries\Todos;

use App\Models\Todo;
use App\Models\TodoDependency;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

final class TodoDependencyQuery
{
    /**
     * @return Collection<int, TodoDependency>
     */
    public function forTodo(User $user, Todo $todo): Collection
    {
        return TodoDependency::query()
            ->ownedBy($user)
            ->where('todo_id', $todo->id)
            ->with(['blocker' => fn ($query) => $query->where('todos.user_id', $user->id)])
            ->oldest()
            ->get();
    }

    /**
     * @return Collection<int, TodoDependency>
     */
    public function openForTodo(User $user, Todo $todo): Collection
    {
        return TodoDependency::query()
            ->ownedBy($user)
            ->where('todo_id', $todo->id)
            ->whereHas('blocker', fn (Builder $blocker): Builder => $blocker
                ->where('todos.user_id', $user->id)
                ->where('todos.is_completed', false))
            ->with(['blocker' => fn ($query) => $query->where('todos.user_id', $user->id)])
            ->oldest()
            ->get();
    }

    /**
     * @return Collection<int, Todo>
     */
    public function candidatesFor(User $user, Todo $todo, int $limit = 50): Collection
    {
        $existingIds = TodoDependency::query()
            ->ownedBy($user)
            ->where('todo_id', $todo->id)
            ->pluck('depends_on_todo_id')
            ->all();

        return Todo::query()
            ->select(['id', 'user_id', 'project_id', 'title', 'priority', 'due_date', 'is_completed', 'archived_at', 'deleted_at', 'created_at', 'updated_at'])
            ->ownedBy($user)
            ->active()
            ->whereKeyNot($todo->id)
            ->whereNotIn('id', $existingIds)
            ->orderBy('title')
            ->limit($limit)
            ->get()
            ->reject(fn (Todo $candidate): bool => $this->wouldCreateCycle($user, $todo, $candidate))
            ->values();
    }

    /**
     * @return Collection<int, Todo>
     */
    public function blockedBy(User $user, Todo $blocker): Collection
    {
        return Todo::query()
            ->select(['id', 'user_id', 'project_id', 'title', 'priority', 'due_date', 'is_completed', 'archived_at', 'deleted_at', 'created_at', 'updated_at'])
            ->ownedBy($user)
            ->active()
            ->whereHas('dependencies', fn (Builder $dependency): Builder => $dependency
                ->where('depends_on_todo_id', $blocker->id))
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->get();
    }

    public function findFor(User $user, Todo $todo, int $dependencyId): TodoDependency
    {
        $dependency = TodoDependency::query()
            ->ownedBy($user)
            ->where('todo_id', $todo->id)
            ->with('blocker')
            ->find($dependencyId);

        if ($dependency instanceof TodoDependency) {
            return $dependency;
        }

        throw (new ModelNotFoundException)->setModel(TodoDependency::class, [$dependencyId]);
    }

    public function findCandidateFor(User $user, Todo $todo, int $candidateId): Todo
    {
        return Todo::query()
            ->ownedBy($user)
            ->active()
            ->whereKeyNot($todo->id)
            ->findOrFail($candidateId);
    }

    public function ensureCanAttach(User $user, Todo $todo, Todo $candidate, string $field = 'dependencyTodoId'): void
    {
        if (! $todo->isOwnedBy($user) || ! $candidate->isOwnedBy($user) || ! $candidate->isActive() || $todo->is($candidate)) {
            throw ValidationException::withMessages([
                $field => __('todos.validation.todo_dependency'),
            ]);
        }

        if ($this->exists($user, $todo, $candidate) || $this->wouldCreateCycle($user, $todo, $candidate)) {
            throw ValidationException::withMessages([
                $field => __('todos.validation.todo_dependency'),
            ]);
        }
    }

    public function exists(User $user, Todo $todo, Todo $candidate): bool
    {
        return TodoDependency::query()
            ->ownedBy($user)
            ->where('todo_id', $todo->id)
            ->where('depends_on_todo_id', $candidate->id)
            ->exists();
    }

    public function wouldCreateCycle(User $user, Todo $todo, Todo $candidate): bool
    {
        if ($todo->is($candidate)) {
            return true;
        }

        $targetId = $todo->id;
        $frontier = [$candidate->id];
        $seen = [];

        while ($frontier !== []) {
            $current = array_values(array_diff(array_unique($frontier), $seen));
            $frontier = [];

            if ($current === []) {
                break;
            }

            if (in_array($targetId, $current, true)) {
                return true;
            }

            $seen = array_values(array_unique([...$seen, ...$current]));

            $frontier = TodoDependency::query()
                ->ownedBy($user)
                ->whereIn('todo_id', $current)
                ->pluck('depends_on_todo_id')
                ->map(fn (int|string $id): int => (int) $id)
                ->all();
        }

        return false;
    }
}

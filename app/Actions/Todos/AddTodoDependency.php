<?php

namespace App\Actions\Todos;

use App\Models\Todo;
use App\Models\TodoDependency;
use App\Models\User;
use App\Queries\Todos\TodoDependencyQuery;
use Illuminate\Support\Facades\Gate;

final class AddTodoDependency
{
    public function __construct(
        private readonly TodoDependencyQuery $dependencies,
    ) {}

    public function handle(User $user, Todo $todo, int $dependsOnTodoId): TodoDependency
    {
        Gate::forUser($user)->authorize('update', $todo);
        Gate::forUser($user)->authorize('create', TodoDependency::class);

        $candidate = $this->dependencies->findCandidateFor($user, $todo, $dependsOnTodoId);
        $this->dependencies->ensureCanAttach($user, $todo, $candidate);

        return $user->todoDependencies()->create([
            'todo_id' => $todo->id,
            'depends_on_todo_id' => $candidate->id,
        ])->refresh()->load(['todo', 'blocker']);
    }
}

<?php

namespace App\Actions\Todos;

use App\Models\TodoDependency;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class RemoveTodoDependency
{
    public function handle(User $user, TodoDependency $dependency): void
    {
        Gate::forUser($user)->authorize('delete', $dependency);

        $dependency->delete();
    }
}

<?php

namespace App\Actions\Todos;

use App\Models\TodoTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class DeleteTodoTemplate
{
    public function handle(User $user, TodoTemplate $template): void
    {
        Gate::forUser($user)->authorize('delete', $template);

        $template->delete();
    }
}

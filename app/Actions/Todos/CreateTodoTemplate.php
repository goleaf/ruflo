<?php

namespace App\Actions\Todos;

use App\Data\Todos\TodoTemplateData;
use App\Models\TodoTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class CreateTodoTemplate
{
    public function handle(User $user, TodoTemplateData $data): TodoTemplate
    {
        Gate::forUser($user)->authorize('create', TodoTemplate::class);
        $this->ensureUniqueName($user, $data->name);

        return $user->todoTemplates()->create($data->toAttributes());
    }

    private function ensureUniqueName(User $user, string $name): void
    {
        if (! $user->todoTemplates()->where('name', $name)->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'name' => __('todos.validation.template_name_unique'),
        ]);
    }
}

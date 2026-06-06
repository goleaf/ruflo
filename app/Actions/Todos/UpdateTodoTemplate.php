<?php

namespace App\Actions\Todos;

use App\Data\Todos\TodoTemplateData;
use App\Models\TodoTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class UpdateTodoTemplate
{
    public function handle(User $user, TodoTemplate $template, TodoTemplateData $data): TodoTemplate
    {
        Gate::forUser($user)->authorize('update', $template);
        $this->ensureUniqueName($user, $template, $data->name);

        $template->forceFill($data->toAttributes())->save();

        return $template->refresh();
    }

    private function ensureUniqueName(User $user, TodoTemplate $template, string $name): void
    {
        $duplicateExists = $user->todoTemplates()
            ->where('name', $name)
            ->whereKeyNot($template->id)
            ->exists();

        if (! $duplicateExists) {
            return;
        }

        throw ValidationException::withMessages([
            'name' => __('todos.validation.template_name_unique'),
        ]);
    }
}

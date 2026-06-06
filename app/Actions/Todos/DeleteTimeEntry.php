<?php

namespace App\Actions\Todos;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class DeleteTimeEntry
{
    public function handle(User $user, TimeEntry $entry): void
    {
        Gate::forUser($user)->authorize('delete', $entry);

        if ($entry->isRunning()) {
            throw ValidationException::withMessages([
                'timer' => __('todos.validation.time_entry_delete_running'),
            ]);
        }

        $entry->delete();
    }
}

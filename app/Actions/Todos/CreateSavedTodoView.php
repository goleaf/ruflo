<?php

namespace App\Actions\Todos;

use App\Data\Todos\SavedTodoViewData;
use App\Models\SavedTodoView;
use App\Models\User;

/**
 * Creates one named saved view inside the user's private workspace.
 */
final class CreateSavedTodoView
{
    public function handle(User $user, SavedTodoViewData $data): SavedTodoView
    {
        return $user->savedTodoViews()->create([
            'name' => $data->name,
            'criteria' => $data->criteria,
        ]);
    }
}

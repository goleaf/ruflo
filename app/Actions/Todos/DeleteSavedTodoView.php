<?php

namespace App\Actions\Todos;

use App\Models\SavedTodoView;

/**
 * Deletes a user's saved task-view preset.
 */
final class DeleteSavedTodoView
{
    public function handle(SavedTodoView $savedTodoView): void
    {
        $savedTodoView->delete();
    }
}

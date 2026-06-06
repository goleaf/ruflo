<?php

namespace App\Actions\Todos;

use App\Events\TodoCommentDeleted;
use App\Models\TodoComment;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class DeleteTodoComment
{
    public function handle(User $actor, TodoComment $comment): void
    {
        Gate::forUser($actor)->authorize('delete', $comment);

        $comment->delete();

        TodoCommentDeleted::dispatch($comment);
    }
}

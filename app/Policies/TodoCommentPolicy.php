<?php

namespace App\Policies;

use App\Models\Todo;
use App\Models\TodoComment;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

final class TodoCommentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TodoComment $todoComment): Response
    {
        return $this->canViewParentTodo($user, $todoComment)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, ?Todo $todo = null): Response
    {
        $canCreate = $todo instanceof Todo
            && ! $todo->trashed()
            && Gate::forUser($user)->allows('comment', $todo);

        return $canCreate
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TodoComment $todoComment): Response
    {
        return $this->canAuthorMutate($user, $todoComment)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TodoComment $todoComment): Response
    {
        return $this->canAuthorMutate($user, $todoComment)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TodoComment $todoComment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TodoComment $todoComment): bool
    {
        return false;
    }

    private function canViewParentTodo(User $user, TodoComment $todoComment): bool
    {
        $todo = $todoComment->todo()->first();

        if ($todo === null || $todo->user_id !== $todoComment->user_id) {
            return false;
        }

        return Gate::forUser($user)->allows('view', $todo);
    }

    private function canAuthorMutate(User $user, TodoComment $todoComment): bool
    {
        if ($todoComment->trashed() || ! $todoComment->isAuthoredBy($user)) {
            return false;
        }

        return $this->canViewParentTodo($user, $todoComment);
    }
}

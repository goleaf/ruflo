<?php

namespace App\Policies;

use App\Models\TodoCommentMention;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class TodoCommentMentionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TodoCommentMention $todoCommentMention): bool
    {
        return Gate::forUser($user)->allows('view', $todoCommentMention->comment);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TodoCommentMention $todoCommentMention): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TodoCommentMention $todoCommentMention): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TodoCommentMention $todoCommentMention): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TodoCommentMention $todoCommentMention): bool
    {
        return false;
    }
}

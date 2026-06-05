<?php

namespace App\Policies;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class TodoPolicy
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
    public function view(User $user, Todo $todo): Response
    {
        return $this->ownerOnly($user, $todo);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Todo $todo): Response
    {
        return $this->ownerOnly($user, $todo);
    }

    /**
     * Determine whether the user can complete or reopen the model.
     */
    public function complete(User $user, Todo $todo): Response
    {
        return $this->ownerOnly($user, $todo);
    }

    /**
     * Determine whether the user can clear their completed todos.
     */
    public function clearCompleted(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Todo $todo): Response
    {
        return $this->ownerOnly($user, $todo);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Todo $todo): Response
    {
        return $this->ownerOnly($user, $todo);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Todo $todo): bool
    {
        return false;
    }

    private function ownerOnly(User $user, Todo $todo): Response
    {
        return $todo->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}

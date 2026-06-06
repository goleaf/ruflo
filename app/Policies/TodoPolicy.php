<?php

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use App\Support\Projects\ProjectAccess;
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
        return $this->canView($user, $todo)
            ? Response::allow()
            : Response::denyAsNotFound();
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
        return $this->canEdit($user, $todo)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can complete the model.
     */
    public function complete(User $user, Todo $todo): Response
    {
        return $this->canEdit($user, $todo)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can reopen a completed task.
     */
    public function reopen(User $user, Todo $todo): Response
    {
        return $this->canEdit($user, $todo)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can comment on the task.
     */
    public function comment(User $user, Todo $todo): Response
    {
        return $this->canEdit($user, $todo)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can archive the model.
     */
    public function archive(User $user, Todo $todo): Response
    {
        return $this->canManage($user, $todo)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can clear their completed todos.
     */
    public function clearCompleted(User $user): bool
    {
        return true;
    }

    public function bulkComplete(User $user): bool
    {
        return true;
    }

    public function bulkArchive(User $user): bool
    {
        return true;
    }

    public function bulkUnarchive(User $user): bool
    {
        return true;
    }

    public function bulkDelete(User $user): bool
    {
        return true;
    }

    public function bulkRestoreDeleted(User $user): bool
    {
        return true;
    }

    public function bulkMove(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Todo $todo): Response
    {
        return $this->canManage($user, $todo)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can unarchive the model.
     */
    public function unarchive(User $user, Todo $todo): Response
    {
        return $this->canManage($user, $todo)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can restore a soft-deleted model.
     */
    public function restore(User $user, Todo $todo): Response
    {
        return $this->canManage($user, $todo)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Todo $todo): bool
    {
        return false;
    }

    private function canView(User $user, Todo $todo): bool
    {
        if ($todo->isOwnedBy($user)) {
            return true;
        }

        if ($todo->project_id === null) {
            return false;
        }

        return $this->roleForTodoProject($user, $todo) !== null;
    }

    private function canEdit(User $user, Todo $todo): bool
    {
        if ($todo->isOwnedBy($user)) {
            return true;
        }

        if ($todo->project_id === null) {
            return false;
        }

        return $this->roleForTodoProject($user, $todo)?->canEditTasks() ?? false;
    }

    private function canManage(User $user, Todo $todo): bool
    {
        if ($todo->isOwnedBy($user)) {
            return true;
        }

        if ($todo->project_id === null) {
            return false;
        }

        return $this->roleForTodoProject($user, $todo)?->canManageTasks() ?? false;
    }

    private function roleForTodoProject(User $user, Todo $todo): ?ProjectRole
    {
        $project = $todo->relationLoaded('project')
            ? $todo->project
            : $todo->project()->select(['id', 'user_id'])->first();

        if (! $project instanceof Project || (int) $project->user_id !== (int) $todo->user_id) {
            return null;
        }

        return app(ProjectAccess::class)->roleFor($user, $project);
    }
}

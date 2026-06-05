<?php

namespace App\Actions\Projects;

use App\Models\Project;

/**
 * Deletes a project.
 *
 * Tasks are deliberately NOT deleted: the `todos.project_id` foreign key is
 * nulled on delete, so the project's tasks fall back to "no project" and the
 * user never loses work by removing a grouping.
 */
final class DeleteProject
{
    public function handle(Project $project): void
    {
        $project->delete();
    }
}

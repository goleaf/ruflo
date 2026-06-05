<?php

namespace App\Actions\Projects;

use App\Models\Project;

/**
 * Archives a project: it disappears from active pickers and filters without
 * being deleted, and its tasks are untouched. Idempotent.
 */
final class ArchiveProject
{
    public function handle(Project $project): Project
    {
        if (! $project->isArchived()) {
            $project->archived_at = now();
            $project->save();
        }

        return $project;
    }
}

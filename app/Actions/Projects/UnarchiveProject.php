<?php

namespace App\Actions\Projects;

use App\Models\Project;

/**
 * Restores an archived project back to active. Idempotent.
 */
final class UnarchiveProject
{
    public function handle(Project $project): Project
    {
        if ($project->isArchived()) {
            $project->archived_at = null;
            $project->save();
        }

        return $project;
    }
}

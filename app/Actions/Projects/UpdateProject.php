<?php

namespace App\Actions\Projects;

use App\Data\Projects\ProjectData;
use App\Models\Project;

/**
 * Renames a project and/or changes its color. Never changes ownership.
 */
final class UpdateProject
{
    public function handle(Project $project, ProjectData $data): Project
    {
        $project->update([
            'name' => $data->name,
            'color' => $data->color,
        ]);

        return $project;
    }
}

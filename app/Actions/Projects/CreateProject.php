<?php

namespace App\Actions\Projects;

use App\Data\Projects\ProjectData;
use App\Models\Project;
use App\Models\User;

/**
 * Creates a project owned by the given user. Ownership is assigned through the
 * relationship, never from request input.
 */
final class CreateProject
{
    public function handle(User $user, ProjectData $data): Project
    {
        return $user->projects()->create([
            'name' => $data->name,
            'color' => $data->color,
        ]);
    }
}

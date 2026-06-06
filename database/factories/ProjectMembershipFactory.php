<?php

namespace Database\Factories;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectMembership>
 */
class ProjectMembershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'added_by_user_id' => null,
            'role' => ProjectRole::Viewer,
            'removed_at' => null,
        ];
    }

    public function forProject(Project $project): static
    {
        return $this
            ->for($project)
            ->state(fn (array $attributes): array => [
                'added_by_user_id' => $project->user_id,
            ]);
    }

    public function forMember(User $user): static
    {
        return $this->for($user, 'user');
    }

    public function manager(): static
    {
        return $this->role(ProjectRole::Manager);
    }

    public function editor(): static
    {
        return $this->role(ProjectRole::Editor);
    }

    public function viewer(): static
    {
        return $this->role(ProjectRole::Viewer);
    }

    public function removed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'removed_at' => now(),
        ]);
    }

    private function role(ProjectRole $role): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => $role,
        ]);
    }
}

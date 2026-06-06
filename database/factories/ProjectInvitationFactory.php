<?php

namespace Database\Factories;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProjectInvitation>
 */
class ProjectInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $token = Str::random(48);

        return [
            'project_id' => Project::factory(),
            'invited_by_user_id' => User::factory(),
            'accepted_by_user_id' => null,
            'role' => ProjectRole::Viewer,
            'token' => $token,
            'token_hash' => ProjectInvitation::hashToken($token),
            'expires_at' => now()->addDays(7),
            'cancelled_at' => null,
            'accepted_at' => null,
        ];
    }

    public function forProject(Project $project): static
    {
        return $this
            ->for($project)
            ->state(fn (array $attributes): array => [
                'invited_by_user_id' => $project->user_id,
            ]);
    }

    public function invitedBy(User $user): static
    {
        return $this->for($user, 'invitedBy');
    }

    public function withToken(string $token): static
    {
        return $this->state(fn (array $attributes): array => [
            'token' => $token,
            'token_hash' => ProjectInvitation::hashToken($token),
        ]);
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

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'cancelled_at' => now()->subHour(),
        ]);
    }

    public function accepted(?User $user = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'accepted_by_user_id' => $user?->id ?? User::factory(),
            'accepted_at' => now()->subHour(),
        ]);
    }

    private function role(ProjectRole $role): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => $role,
        ]);
    }
}

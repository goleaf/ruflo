<?php

namespace App\Livewire\Projects;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Projects\ProjectListQuery;
use App\Queries\Projects\ProjectMembershipQuery;
use App\Queries\Todos\TodoListQuery;
use App\Support\Projects\ProjectAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('todos.projects.show.title')]
class Show extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Locked]
    public int $projectId;

    public function mount(int $project, ProjectListQuery $projects): void
    {
        $resolvedProject = $projects->findAccessibleFor($this->currentUser(), $project);

        $this->authorize('view', $resolvedProject);

        $this->projectId = $resolvedProject->id;
    }

    public function render(): View
    {
        return view('livewire.projects.show');
    }

    #[Computed]
    public function project(): Project
    {
        $project = app(ProjectListQuery::class)->findAccessibleFor($this->currentUser(), $this->projectId);

        $this->authorize('view', $project);

        return $project;
    }

    /**
     * @return LengthAwarePaginator<int, Todo>
     */
    #[Computed]
    public function todos(): LengthAwarePaginator
    {
        return app(TodoListQuery::class)
            ->forProjectDetail($this->currentUser(), $this->project)
            ->paginate(12);
    }

    /**
     * @return array{active: int, completed: int, archived: int, trash: int}
     */
    #[Computed]
    public function summary(): array
    {
        return app(TodoListQuery::class)->projectSummaryFor($this->currentUser(), $this->project);
    }

    /**
     * @return Collection<int, ProjectMembership>
     */
    #[Computed]
    public function memberships(): Collection
    {
        return app(ProjectMembershipQuery::class)->listForProject($this->project);
    }

    #[Computed]
    public function accessRole(): ProjectRole
    {
        $role = app(ProjectAccess::class)->roleFor($this->currentUser(), $this->project);

        abort_unless($role instanceof ProjectRole, 404);

        return $role;
    }

    #[Computed]
    public function isSharedProject(): bool
    {
        return $this->memberships->isNotEmpty();
    }

    #[Computed]
    public function canManageMembers(): bool
    {
        return $this->accessRole->canManageMembers();
    }

    #[Computed]
    public function canUseTaskFilter(): bool
    {
        return $this->project->isOwnedBy($this->currentUser());
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

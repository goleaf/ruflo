<?php

namespace App\Livewire\Projects;

use App\Actions\Projects\CancelProjectInvitation;
use App\Actions\Projects\CreateProjectInvitation;
use App\Actions\Projects\RemoveProjectMember;
use App\Actions\Projects\UpdateProjectMemberRole;
use App\Enums\ProjectRole;
use App\Http\Requests\Projects\StoreProjectInvitationRequest;
use App\Http\Requests\Projects\UpdateProjectMembershipRequest;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\ProjectMembership;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Projects\ProjectInvitationQuery;
use App\Queries\Projects\ProjectListQuery;
use App\Queries\Projects\ProjectMembershipQuery;
use App\Queries\Todos\TodoListQuery;
use App\Rules\Projects\ProjectInvitationExpiryDays;
use App\Support\Projects\ProjectAccess;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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

    public string $inviteRole = 'viewer';

    public string $inviteExpiresInDays = '7';

    #[Locked]
    public ?int $editingMembershipId = null;

    public string $memberRole = 'viewer';

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

    public function createInvitation(CreateProjectInvitation $createInvitation): void
    {
        $this->authorize('manageMembers', $this->project);

        $validated = $this->validate(
            StoreProjectInvitationRequest::baseRules(),
            attributes: StoreProjectInvitationRequest::attributeNames(),
        );

        $expiresInDays = ProjectInvitationExpiryDays::normalize($validated['inviteExpiresInDays']);

        if ($expiresInDays === null) {
            $this->addError('inviteExpiresInDays', __('todos.collaboration.invites.validation.expires_in_days'));

            return;
        }

        $createInvitation->handle(
            $this->currentUser(),
            $this->project,
            ProjectRole::from((string) $validated['inviteRole']),
            $expiresInDays,
        );

        $this->inviteRole = ProjectRole::Viewer->value;
        $this->inviteExpiresInDays = '7';
        $this->clearInviteState();

        Flux::modal('project-invite-create')->close();
        Flux::toast(variant: 'success', text: __('todos.collaboration.invites.messages.created'));
    }

    public function cancelInvitation(int $invitationId, ProjectInvitationQuery $invitations, CancelProjectInvitation $cancelInvitation): void
    {
        $invitation = $invitations->findForProject($this->project, $invitationId);

        $this->authorize('delete', $invitation);

        $cancelInvitation->handle($this->currentUser(), $this->project, $invitation);
        $this->clearInviteState();

        Flux::toast(variant: 'success', text: __('todos.collaboration.invites.messages.cancelled'));
    }

    public function prepareMemberRoleEdit(int $membershipId, ProjectMembershipQuery $memberships): void
    {
        $membership = $memberships->findActiveForProject($this->project, $membershipId);

        Gate::forUser($this->currentUser())->authorize('update', $membership);

        $this->editingMembershipId = $membership->id;
        $this->memberRole = $membership->role->value;
        $this->resetValidation('memberRole');

        Flux::modal('project-member-role-edit')->show();
    }

    public function updateMemberRole(ProjectMembershipQuery $memberships, UpdateProjectMemberRole $updateMemberRole): void
    {
        if ($this->editingMembershipId === null) {
            $this->addError('memberRole', __('todos.collaboration.members.validation.member_required'));

            return;
        }

        $validated = $this->validate(
            UpdateProjectMembershipRequest::baseRules(),
            attributes: UpdateProjectMembershipRequest::attributeNames(),
        );

        $membership = $memberships->findActiveForProject($this->project, $this->editingMembershipId);

        $updateMemberRole->handle(
            $this->currentUser(),
            $this->project,
            $membership,
            ProjectRole::from((string) $validated['memberRole']),
        );

        $this->editingMembershipId = null;
        $this->memberRole = ProjectRole::Viewer->value;
        $this->clearMemberState();

        Flux::modal('project-member-role-edit')->close();
        Flux::toast(variant: 'success', text: __('todos.collaboration.members.messages.role_updated'));
    }

    public function removeMember(int $membershipId, ProjectMembershipQuery $memberships, RemoveProjectMember $removeMember): void
    {
        $membership = $memberships->findActiveForProject($this->project, $membershipId);

        Gate::forUser($this->currentUser())->authorize('delete', $membership);

        $removeMember->handle($this->currentUser(), $this->project, $membership->user);
        $this->clearMemberState();

        Flux::toast(variant: 'success', text: __('todos.collaboration.members.messages.removed'));
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

    /**
     * @return Collection<int, ProjectInvitation>
     */
    #[Computed]
    public function invitations(): Collection
    {
        if (! $this->canManageMembers) {
            return new Collection;
        }

        return app(ProjectInvitationQuery::class)->listForProject($this->project);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    #[Computed]
    public function inviteRoleOptions(): array
    {
        return collect(ProjectRole::assignableValues())
            ->map(fn (string $role): array => [
                'value' => $role,
                'label' => ProjectRole::from($role)->label(),
            ])
            ->values()
            ->all();
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

    private function clearInviteState(): void
    {
        unset($this->invitations);
    }

    private function clearMemberState(): void
    {
        unset($this->memberships);
        unset($this->summary);
    }
}

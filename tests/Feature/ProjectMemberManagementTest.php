<?php

use App\Actions\Projects\RemoveProjectMember;
use App\Actions\Projects\UpdateProjectMemberRole;
use App\Enums\ProjectRole;
use App\Livewire\Projects\Show as ProjectShow;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('owners can change member roles and keep owner access protected', function () {
    $owner = User::factory()->create(['email' => 'owner@example.com']);
    $member = User::factory()->create(['name' => 'Managed Member']);
    $project = Project::factory()->for($owner)->create(['name' => 'Owner managed project']);
    $membership = ProjectMembership::factory()->forProject($project)->forMember($member)->viewer()->create();

    Livewire::actingAs($owner)
        ->test(ProjectShow::class, ['project' => $project->id])
        ->assertSee('data-test="project-member-owner"', false)
        ->assertSee('owner@example.com')
        ->assertSee(__('todos.collaboration.members.actions.edit_role'))
        ->call('prepareMemberRoleEdit', $membership->id)
        ->assertSet('memberRole', ProjectRole::Viewer->value)
        ->set('memberRole', ProjectRole::Manager->value)
        ->call('updateMemberRole')
        ->assertHasNoErrors();

    expect($membership->fresh()->role)->toBe(ProjectRole::Manager);

    expect(fn () => app(RemoveProjectMember::class)->handle($member, $project, $owner))
        ->toThrow(ValidationException::class);
});

test('managers can change member roles and remove members from the project page', function () {
    $owner = User::factory()->create();
    $manager = User::factory()->create();
    $viewer = User::factory()->create(['name' => 'Viewer Member', 'email' => 'viewer-member@example.com']);
    $project = Project::factory()->for($owner)->create(['name' => 'Managed project']);
    $todo = Todo::factory()->forProject($project)->create(['title' => 'Managed task']);

    ProjectMembership::factory()->forProject($project)->forMember($manager)->manager()->create();
    $viewerMembership = ProjectMembership::factory()->forProject($project)->forMember($viewer)->viewer()->create();

    Livewire::actingAs($manager)
        ->test(ProjectShow::class, ['project' => $project->id])
        ->assertSee(__('todos.collaboration.members.actions.edit_role'))
        ->assertSee(__('todos.collaboration.members.actions.remove'))
        ->call('prepareMemberRoleEdit', $viewerMembership->id)
        ->set('memberRole', ProjectRole::Editor->value)
        ->call('updateMemberRole')
        ->assertHasNoErrors();

    expect($viewerMembership->fresh()->role)->toBe(ProjectRole::Editor)
        ->and($viewer->can('update', $todo))->toBeTrue();

    Livewire::actingAs($manager)
        ->test(ProjectShow::class, ['project' => $project->id])
        ->call('removeMember', $viewerMembership->id)
        ->assertHasNoErrors();

    expect($viewerMembership->fresh()->removed_at)->not->toBeNull();

    $this->actingAs($viewer)
        ->get(route('projects.show', $project))
        ->assertNotFound()
        ->assertDontSee('Managed project');

    $this->actingAs($viewer)
        ->get(route('todos.show', $todo))
        ->assertNotFound()
        ->assertDontSee('Managed task');
});

test('viewers and editors cannot manage project members', function () {
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $viewer = User::factory()->create();
    $otherViewer = User::factory()->create();
    $project = Project::factory()->for($owner)->create();

    ProjectMembership::factory()->forProject($project)->forMember($editor)->editor()->create();
    $viewerMembership = ProjectMembership::factory()->forProject($project)->forMember($viewer)->viewer()->create();
    $otherViewerMembership = ProjectMembership::factory()->forProject($project)->forMember($otherViewer)->viewer()->create();

    $this->actingAs($editor)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertDontSee('data-test="project-member-edit-role', false)
        ->assertDontSee('data-test="project-member-remove', false);

    Livewire::actingAs($editor)
        ->test(ProjectShow::class, ['project' => $project->id])
        ->call('prepareMemberRoleEdit', $viewerMembership->id)
        ->assertSet('editingMembershipId', null);

    expect(fn () => app(UpdateProjectMemberRole::class)->handle($editor, $project, $viewerMembership, ProjectRole::Manager))
        ->toThrow(AuthorizationException::class);

    $this->actingAs($viewer)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertDontSee('data-test="project-member-edit-role', false)
        ->assertDontSee('data-test="project-member-remove', false);

    Livewire::actingAs($viewer)
        ->test(ProjectShow::class, ['project' => $project->id])
        ->call('removeMember', $otherViewerMembership->id)
        ->assertHasNoErrors();

    expect($otherViewerMembership->fresh()->removed_at)->toBeNull();

    expect(fn () => app(RemoveProjectMember::class)->handle($viewer, $project, $otherViewer))
        ->toThrow(AuthorizationException::class);
});

test('member role updates reject owner roles and foreign membership ids', function () {
    $owner = User::factory()->create();
    $manager = User::factory()->create();
    $viewer = User::factory()->create();
    $foreignMember = User::factory()->create();
    $project = Project::factory()->for($owner)->create();
    $foreignProject = Project::factory()->for($owner)->create();

    ProjectMembership::factory()->forProject($project)->forMember($manager)->manager()->create();
    $viewerMembership = ProjectMembership::factory()->forProject($project)->forMember($viewer)->viewer()->create();
    $foreignMembership = ProjectMembership::factory()->forProject($foreignProject)->forMember($foreignMember)->viewer()->create();

    Livewire::actingAs($manager)
        ->test(ProjectShow::class, ['project' => $project->id])
        ->call('prepareMemberRoleEdit', $viewerMembership->id)
        ->set('memberRole', ProjectRole::Owner->value)
        ->call('updateMemberRole')
        ->assertHasErrors(['memberRole']);

    expect(fn () => Livewire::actingAs($manager)
        ->test(ProjectShow::class, ['project' => $project->id])
        ->call('removeMember', $foreignMembership->id))
        ->toThrow(ModelNotFoundException::class);
});

test('removed memberships cannot be role updated or used for old links', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $project = Project::factory()->for($owner)->create(['name' => 'Revoked managed project']);
    $todo = Todo::factory()->forProject($project)->create(['title' => 'Revoked managed task']);
    $membership = ProjectMembership::factory()->forProject($project)->forMember($viewer)->viewer()->removed()->create();

    expect(fn () => app(UpdateProjectMemberRole::class)->handle($owner, $project, $membership, ProjectRole::Editor))
        ->toThrow(ModelNotFoundException::class);

    $this->actingAs($viewer)
        ->get(route('projects.show', $project))
        ->assertNotFound()
        ->assertDontSee('Revoked managed project');

    $this->actingAs($viewer)
        ->get(route('todos.show', $todo))
        ->assertNotFound()
        ->assertDontSee('Revoked managed task');
});

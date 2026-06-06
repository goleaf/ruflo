<?php

use App\Actions\Projects\AddProjectMember;
use App\Actions\Projects\RemoveProjectMember;
use App\Enums\ProjectRole;
use App\Livewire\Todos\Show as TodoShow;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Todo;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\ProjectMembershipSeeder;
use Database\Seeders\TodoSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('project roles enforce visibility editing and management boundaries', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $editor = User::factory()->create();
    $manager = User::factory()->create();
    $outsider = User::factory()->create();

    $project = Project::factory()->for($owner)->create(['name' => 'Shared launch']);
    $todo = Todo::factory()->forProject($project)->create(['title' => 'Shared task']);

    ProjectMembership::factory()->forProject($project)->forMember($viewer)->viewer()->create();
    ProjectMembership::factory()->forProject($project)->forMember($editor)->editor()->create();
    ProjectMembership::factory()->forProject($project)->forMember($manager)->manager()->create();

    expect($owner->can('view', $project))->toBeTrue()
        ->and($owner->can('update', $project))->toBeTrue()
        ->and($owner->can('delete', $project))->toBeTrue()
        ->and($owner->can('manageMembers', $project))->toBeTrue()
        ->and($viewer->can('view', $project))->toBeTrue()
        ->and($viewer->can('update', $project))->toBeFalse()
        ->and($viewer->can('manageMembers', $project))->toBeFalse()
        ->and($viewer->can('view', $todo))->toBeTrue()
        ->and($viewer->can('update', $todo))->toBeFalse()
        ->and($editor->can('view', $project))->toBeTrue()
        ->and($editor->can('manageMembers', $project))->toBeFalse()
        ->and($editor->can('update', $todo))->toBeTrue()
        ->and($editor->can('delete', $todo))->toBeFalse()
        ->and($manager->can('view', $project))->toBeTrue()
        ->and($manager->can('update', $project))->toBeTrue()
        ->and($manager->can('archive', $project))->toBeFalse()
        ->and($manager->can('delete', $project))->toBeFalse()
        ->and($manager->can('manageMembers', $project))->toBeTrue()
        ->and($manager->can('delete', $todo))->toBeTrue()
        ->and($outsider->can('view', $project))->toBeFalse()
        ->and($outsider->can('view', $todo))->toBeFalse();
});

test('shared project detail renders active members roles and shared tasks', function () {
    $owner = User::factory()->create(['name' => 'Owner User', 'email' => 'owner@example.com']);
    $viewer = User::factory()->create(['name' => 'Viewer User', 'email' => 'viewer@example.com']);
    $project = Project::factory()->for($owner)->create(['name' => 'Shared operations']);
    $todo = Todo::factory()->forProject($project)->create(['title' => 'Visible shared work']);

    ProjectMembership::factory()->forProject($project)->forMember($viewer)->viewer()->create();

    $this->actingAs($viewer)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSee('Shared operations')
        ->assertSee('Visible shared work')
        ->assertSee(route('todos.show', $todo), false)
        ->assertSee(__('todos.collaboration.scope.shared'))
        ->assertSee(__('todos.collaboration.members.heading'))
        ->assertSee('owner@example.com')
        ->assertSee('viewer@example.com')
        ->assertSee(ProjectRole::Owner->label())
        ->assertSee(ProjectRole::Viewer->label())
        ->assertDontSee(__('todos.projects.actions.filter_tasks'));
});

test('editors can update shared tasks without losing the shared project link', function () {
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $project = Project::factory()->for($owner)->create();
    $todo = Todo::factory()->forProject($project)->create(['title' => 'Original shared title']);

    ProjectMembership::factory()->forProject($project)->forMember($editor)->editor()->create();

    Livewire::actingAs($editor)
        ->test(TodoShow::class, ['todo' => $todo->id])
        ->set('form.title', 'Updated by shared editor')
        ->call('saveDetails')
        ->assertHasNoErrors();

    $updatedTodo = $todo->fresh();

    expect($updatedTodo->title)->toBe('Updated by shared editor')
        ->and($updatedTodo->project_id)->toBe($project->id);
});

test('removed members lose old-link access to shared project and task detail', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $project = Project::factory()->for($owner)->create(['name' => 'Revoked project']);
    $todo = Todo::factory()->forProject($project)->create(['title' => 'Revoked task']);
    $membership = ProjectMembership::factory()->forProject($project)->forMember($viewer)->viewer()->create();

    $this->actingAs($viewer)->get(route('projects.show', $project))->assertOk();
    $this->actingAs($viewer)->get(route('todos.show', $todo))->assertOk();

    $membership->forceFill(['removed_at' => now()])->save();

    $this->actingAs($viewer)
        ->get(route('projects.show', $project))
        ->assertNotFound()
        ->assertDontSee('Revoked project');

    $this->actingAs($viewer)
        ->get(route('todos.show', $todo))
        ->assertNotFound()
        ->assertDontSee('Revoked task');
});

test('member actions reject owner membership and owner removal', function () {
    $owner = User::factory()->create();
    $manager = User::factory()->create();
    $project = Project::factory()->for($owner)->create();

    ProjectMembership::factory()->forProject($project)->forMember($manager)->manager()->create();

    expect(fn () => app(AddProjectMember::class)->handle($owner, $project, $owner, ProjectRole::Viewer))
        ->toThrow(ValidationException::class);

    expect(fn () => app(AddProjectMember::class)->handle($owner, $project, $manager, ProjectRole::Owner))
        ->toThrow(ValidationException::class);

    expect(fn () => app(RemoveProjectMember::class)->handle($manager, $project, $owner))
        ->toThrow(ValidationException::class);
});

test('project membership seeder creates idempotent local demo sharing', function () {
    $this->seed([DemoUserSeeder::class, TodoSeeder::class, ProjectMembershipSeeder::class]);

    $avery = User::query()->where('email', 'test@example.com')->firstOrFail();
    $morgan = User::query()->where('email', 'second@example.com')->firstOrFail();

    expect(ProjectMembership::query()->count())->toBe(3)
        ->and(ProjectMembership::query()->where('user_id', $morgan->id)->where('role', ProjectRole::Editor->value)->exists())->toBeTrue()
        ->and(ProjectMembership::query()->where('user_id', $morgan->id)->where('role', ProjectRole::Viewer->value)->exists())->toBeTrue()
        ->and(ProjectMembership::query()->where('user_id', $avery->id)->where('role', ProjectRole::Manager->value)->exists())->toBeTrue();

    $this->seed(ProjectMembershipSeeder::class);

    expect(ProjectMembership::query()->count())->toBe(3);
});

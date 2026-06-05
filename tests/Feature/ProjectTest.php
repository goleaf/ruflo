<?php

use App\Livewire\Todos\Index;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Step 4 — Projects (owner-scoped task grouping)
|--------------------------------------------------------------------------
*/

it('lets a user create a project in their own workspace', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(Index::class)
        ->set('newProjectName', 'Home renovation')
        ->call('createProject')
        ->assertHasNoErrors();

    expect($user->projects()->where('name', 'Home renovation')->exists())->toBeTrue();
});

it('rejects an empty project name', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(Index::class)
        ->set('newProjectName', '')
        ->call('createProject')
        ->assertHasErrors(['newProjectName' => 'required']);
});

it('lets a user rename their own project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['name' => 'Old name']);

    Livewire::actingAs($user)->test(Index::class)
        ->call('startRenameProject', $project->id)
        ->assertSet('editingProjectName', 'Old name')
        ->set('editingProjectName', 'New name')
        ->call('saveProjectName')
        ->assertHasNoErrors()
        ->assertSet('editingProjectId', null);

    expect($project->fresh()->name)->toBe('New name');
});

it('rejects an empty project rename', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['name' => 'Keep name']);

    Livewire::actingAs($user)->test(Index::class)
        ->call('startRenameProject', $project->id)
        ->set('editingProjectName', '')
        ->call('saveProjectName')
        ->assertHasErrors(['editingProjectName' => 'required']);

    expect($project->fresh()->name)->toBe('Keep name');
});

it('denies project access to non-owners', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $project = Project::factory()->for($owner)->create();

    expect(Gate::forUser($intruder)->denies('view', $project))->toBeTrue()
        ->and(Gate::forUser($intruder)->denies('update', $project))->toBeTrue()
        ->and(Gate::forUser($intruder)->denies('delete', $project))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('view', $project))->toBeTrue();
});

it('does not show another users projects in the picker', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    Project::factory()->for($owner)->create(['name' => 'Mine']);
    Project::factory()->for($other)->create(['name' => 'Theirs']);

    Livewire::actingAs($owner)->test(Index::class)
        ->assertSee('Mine')
        ->assertDontSee('Theirs');
});

it('forbids archiving or deleting another users project', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $project = Project::factory()->for($owner)->create();

    expect(fn () => Livewire::actingAs($intruder)->test(Index::class)->call('startRenameProject', $project->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => Livewire::actingAs($intruder)->test(Index::class)->call('archiveProject', $project->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => Livewire::actingAs($intruder)->test(Index::class)->call('deleteProject', $project->id))
        ->toThrow(ModelNotFoundException::class);

    expect($project->fresh())->not->toBeNull()
        ->and($project->fresh()->isArchived())->toBeFalse();
});

it('keeps tasks when a project is deleted, moving them to no project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $todo = Todo::factory()->for($user)->create(['project_id' => $project->id]);

    Livewire::actingAs($user)->test(Index::class)->call('deleteProject', $project->id);

    expect(Project::query()->find($project->id))->toBeNull()
        ->and($todo->fresh())->not->toBeNull()
        ->and($todo->fresh()->project_id)->toBeNull();
});

it('archives and restores a project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    Livewire::actingAs($user)->test(Index::class)->call('archiveProject', $project->id);
    expect($project->fresh()->isArchived())->toBeTrue();

    Livewire::actingAs($user)->test(Index::class)->call('restoreProject', $project->id);
    expect($project->fresh()->isArchived())->toBeFalse();
});

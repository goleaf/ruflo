<?php

use App\Actions\Todos\UpdateTodo;
use App\Data\Todos\TodoData;
use App\Enums\Priority;
use App\Events\TodoUpdated;
use App\Exceptions\InvalidTodoTransition;
use App\Livewire\Todos\Index;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

test('users can edit their own active task details', function () {
    $user = User::factory()->create();
    $oldProject = Project::factory()->for($user)->create(['name' => 'Old project']);
    $newProject = Project::factory()->for($user)->create(['name' => 'New project']);
    $oldTag = Tag::factory()->for($user)->create(['name' => 'old']);
    $newTag = Tag::factory()->for($user)->create(['name' => 'new']);
    $todo = Todo::factory()
        ->forProject($oldProject)
        ->withTags($oldTag)
        ->lowPriority()
        ->create(['title' => 'Original task']);

    Event::fake([TodoUpdated::class]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('startEdit', $todo->id)
        ->assertSet('showEditModal', true)
        ->assertSet('editForm.title', 'Original task')
        ->assertSet('editForm.priority', Priority::Low->value)
        ->set('editForm.title', '  Revised task  ')
        ->set('editForm.priority', Priority::Urgent->value)
        ->set('editForm.due_date', today()->toDateString())
        ->set('editForm.project_id', (string) $newProject->id)
        ->set('editForm.tag_ids', [$newTag->id])
        ->call('saveEdit')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false);

    $todo->refresh();

    expect($todo->title)->toBe('Revised task')
        ->and($todo->priority)->toBe(Priority::Urgent)
        ->and($todo->due_date->toDateString())->toBe(today()->toDateString())
        ->and($todo->project_id)->toBe($newProject->id)
        ->and($todo->is_completed)->toBeFalse()
        ->and($todo->archived_at)->toBeNull()
        ->and($todo->tags()->pluck('tags.id')->all())->toBe([$newTag->id]);

    Event::assertDispatched(TodoUpdated::class, fn (TodoUpdated $event): bool => $event->todo->is($todo));
});

test('update action trims titles and re-scopes organization ids when validation is bypassed', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['title' => 'Original']);
    $ownedTag = Tag::factory()->for($user)->create();
    $foreignProject = Project::factory()->create();
    $foreignTag = Tag::factory()->create();

    app(UpdateTodo::class)->handle($user, $todo, new TodoData(
        title: '  Action edit  ',
        projectId: $foreignProject->id,
        tagIds: [$ownedTag->id, $foreignTag->id],
    ));

    $todo->refresh();

    expect($todo->title)->toBe('Action edit')
        ->and($todo->project_id)->toBeNull()
        ->and($todo->tags()->pluck('tags.id')->all())->toBe([$ownedTag->id]);
});

test('foreign task ids cannot open the edit modal', function () {
    $viewer = User::factory()->create();
    $owner = User::factory()->create();
    $foreignTodo = Todo::factory()->for($owner)->create();

    expect(fn () => Livewire::actingAs($viewer)
        ->test(Index::class)
        ->call('startEdit', $foreignTodo->id))
        ->toThrow(ModelNotFoundException::class);
});

test('invalid edit input is rejected without changing the task', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['title' => 'Keep this']);
    $foreignProject = Project::factory()->create();
    $foreignTag = Tag::factory()->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('startEdit', $todo->id)
        ->set('editForm.priority', 'impossible')
        ->set('editForm.due_date', 'not-a-date')
        ->set('editForm.project_id', (string) $foreignProject->id)
        ->set('editForm.tag_ids', [$foreignTag->id])
        ->call('saveEdit')
        ->assertHasErrors([
            'editForm.priority',
            'editForm.due_date',
            'editForm.project_id',
            'editForm.tag_ids.0',
        ])
        ->assertSet('showEditModal', true);

    $todo->refresh();

    expect($todo->title)->toBe('Keep this')
        ->and($todo->project_id)->toBeNull()
        ->and($todo->tags()->count())->toBe(0);
});

test('archived tasks cannot be edited through the action layer', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->archived()->create(['title' => 'Archived task']);

    expect(fn () => app(UpdateTodo::class)->handle($user, $todo, new TodoData(title: 'Edited archived task')))
        ->toThrow(InvalidTodoTransition::class);

    expect($todo->refresh()->title)->toBe('Archived task');
});

test('edit modal renders errors beside every editable field', function () {
    $source = file_get_contents(resource_path('views/livewire/todos/index.blade.php'));

    expect($source)
        ->toContain('name="editForm.title"')
        ->toContain('name="editForm.priority"')
        ->toContain('name="editForm.due_date"')
        ->toContain('name="editForm.project_id"')
        ->toContain("@error('editForm.tag_ids.*')");
});

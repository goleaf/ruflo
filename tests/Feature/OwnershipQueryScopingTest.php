<?php

use App\Livewire\Todos\Index;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Projects\ProjectListQuery;
use App\Queries\Tags\TagListQuery;
use App\Queries\Todos\TodoFilters;
use App\Queries\Todos\TodoListQuery;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

test('project and tag picker queries are scoped to the current user', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $ownerProject = Project::factory()->for($owner)->create(['name' => 'Mine']);
    Project::factory()->for($other)->create(['name' => 'Theirs']);
    $ownerTag = Tag::factory()->for($owner)->create(['name' => 'mine']);
    Tag::factory()->for($other)->create(['name' => 'theirs']);

    expect(app(ProjectListQuery::class)->activeFor($owner)->pluck('id')->all())->toBe([$ownerProject->id])
        ->and(app(TagListQuery::class)->allFor($owner)->pluck('id')->all())->toBe([$ownerTag->id]);
});

test('tampered foreign project and tag filters return an empty scoped result', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $todo = Todo::factory()->for($owner)->create(['title' => 'Owner task']);
    $foreignProject = Project::factory()->for($other)->create();
    $foreignTag = Tag::factory()->for($other)->create();

    $todo->forceFill(['project_id' => $foreignProject->id])->save();
    DB::table('tag_todo')->insert([
        'tag_id' => $foreignTag->id,
        'todo_id' => $todo->id,
    ]);

    $query = app(TodoListQuery::class);

    expect($query->filtered($owner, new TodoFilters(projectId: $foreignProject->id))->pluck('title')->all())->toBe([])
        ->and($query->filtered($owner, new TodoFilters(tagId: $foreignTag->id))->pluck('title')->all())->toBe([]);
});

test('archived owned project filters return an empty scoped result', function () {
    $owner = User::factory()->create();
    $archivedProject = Project::factory()->for($owner)->archived()->create();
    Todo::factory()->for($owner)->create([
        'project_id' => $archivedProject->id,
        'title' => 'Archived project task',
    ]);

    expect(app(TodoListQuery::class)
        ->filtered($owner, new TodoFilters(projectId: $archivedProject->id))
        ->pluck('title')
        ->all())->toBe([]);
});

test('edit form tag hydration stays inside the current users workspace', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $todo = Todo::factory()->for($owner)->create();
    $ownerTag = Tag::factory()->for($owner)->create(['name' => 'mine']);
    $foreignTag = Tag::factory()->for($other)->create(['name' => 'theirs']);

    $todo->tags()->attach($ownerTag);
    DB::table('tag_todo')->insert([
        'tag_id' => $foreignTag->id,
        'todo_id' => $todo->id,
    ]);

    $component = Livewire::actingAs($owner)
        ->test(Index::class)
        ->call('startEdit', $todo->id)
        ->assertSet('editingId', $todo->id)
        ->assertSet('showEditModal', true);

    expect($component->instance()->editForm->tag_ids)->toBe([$ownerTag->id]);
});

test('server assigned todo edit ids are locked Livewire state', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Index.php'));

    expect($source)
        ->toContain('use Livewire\Attributes\Locked;')
        ->toMatch('/#\[Locked\]\s+public \?int \$editingId = null;/')
        ->toMatch('/#\[Locked\]\s+public \?int \$editingProjectId = null;/');
});

test('todo Livewire component delegates private reads to scoped query objects', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Index.php'));

    expect($source)
        ->toContain('TodoListQuery')
        ->toContain('ProjectListQuery')
        ->toContain('TagListQuery')
        ->toContain('findVisibleFor($this->currentUser()')
        ->not->toContain('Todo::query()')
        ->not->toContain('Project::query()')
        ->not->toContain('Tag::query()')
        ->not->toContain('::find(')
        ->not->toContain('::findOrFail(');
});

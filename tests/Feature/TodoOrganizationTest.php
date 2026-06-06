<?php

use App\Enums\Priority;
use App\Enums\TodoStatus;
use App\Livewire\Todos\Index;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Todos\TodoFilters;
use App\Queries\Todos\TodoListQuery;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Step 4 — Organization: priority, due dates, projects, tags
|--------------------------------------------------------------------------
*/

it('creates a task with priority, due date, project and tags', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $tag = Tag::factory()->for($user)->create();

    Livewire::actingAs($user)->test(Index::class)
        ->set('form.title', 'Organized task')
        ->set('form.priority', 'high')
        ->set('form.due_date', today()->toDateString())
        ->set('form.project_id', (string) $project->id)
        ->set('form.tag_ids', [$tag->id])
        ->call('createTodo')
        ->assertHasNoErrors();

    $todo = $user->todos()->firstWhere('title', 'Organized task');

    expect($todo->priority)->toBe(Priority::High)
        ->and($todo->due_date->toDateString())->toBe(today()->toDateString())
        ->and($todo->project_id)->toBe($project->id)
        ->and($todo->tags->pluck('id')->all())->toBe([$tag->id]);
});

it('rejects a project that belongs to another user', function () {
    $user = User::factory()->create();
    $foreignProject = Project::factory()->create();

    Livewire::actingAs($user)->test(Index::class)
        ->set('form.title', 'Hijack attempt')
        ->set('form.project_id', (string) $foreignProject->id)
        ->call('createTodo')
        ->assertHasErrors(['form.project_id']);

    expect($user->todos()->where('title', 'Hijack attempt')->exists())->toBeFalse();
});

it('rejects an archived project when creating a task', function () {
    $user = User::factory()->create();
    $archivedProject = Project::factory()->for($user)->archived()->create();

    Livewire::actingAs($user)->test(Index::class)
        ->set('form.title', 'Archived project attempt')
        ->set('form.project_id', (string) $archivedProject->id)
        ->call('createTodo')
        ->assertHasErrors(['form.project_id']);

    expect($user->todos()->where('title', 'Archived project attempt')->exists())->toBeFalse();
});

it('rejects tags that belong to another user', function () {
    $user = User::factory()->create();
    $foreignTag = Tag::factory()->create();

    Livewire::actingAs($user)->test(Index::class)
        ->set('form.title', 'Tag hijack')
        ->set('form.tag_ids', [$foreignTag->id])
        ->call('createTodo')
        ->assertHasErrors(['form.tag_ids.0']);

    expect($user->todos()->where('title', 'Tag hijack')->exists())->toBeFalse();
});

it('rejects an invalid priority', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(Index::class)
        ->set('form.title', 'Bad priority')
        ->set('form.priority', 'apocalyptic')
        ->call('createTodo')
        ->assertHasErrors(['form.priority']);
});

it('rejects an invalid due date', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(Index::class)
        ->set('form.title', 'Bad date')
        ->set('form.due_date', 'not-a-date')
        ->call('createTodo')
        ->assertHasErrors(['form.due_date']);
});

/*
|--------------------------------------------------------------------------
| Filters & search — owner-scoped
|--------------------------------------------------------------------------
*/

it('filters tasks by project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    Todo::factory()->for($user)->create(['title' => 'In project', 'project_id' => $project->id]);
    Todo::factory()->for($user)->create(['title' => 'Loose task']);

    Livewire::actingAs($user)->test(Index::class)
        ->set('project', (string) $project->id)
        ->assertSee('In project')
        ->assertDontSee('Loose task')
        ->set('project', 'none')
        ->assertSee('Loose task')
        ->assertDontSee('In project');
});

it('filters tasks by tag', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create();
    $tagged = Todo::factory()->for($user)->create(['title' => 'Tagged task']);
    $tagged->tags()->attach($tag);
    Todo::factory()->for($user)->create(['title' => 'Untagged task']);

    Livewire::actingAs($user)->test(Index::class)
        ->set('tag', (string) $tag->id)
        ->assertSee('Tagged task')
        ->assertDontSee('Untagged task');
});

it('filters tasks by priority', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->priority(Priority::Urgent)->create(['title' => 'Urgent task']);
    Todo::factory()->for($user)->priority(Priority::Low)->create(['title' => 'Low task']);

    Livewire::actingAs($user)->test(Index::class)
        ->set('priorityFilter', 'urgent')
        ->assertSee('Urgent task')
        ->assertDontSee('Low task');
});

it('search returns only the current users matching tasks', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    Todo::factory()->for($owner)->create(['title' => 'Alpha private note']);
    Todo::factory()->for($other)->create(['title' => 'Alpha public note']);

    Livewire::actingAs($owner)->test(Index::class)
        ->set('search', 'Alpha')
        ->assertSee('Alpha private note')
        ->assertDontSee('Alpha public note');
});

it('treats a like wildcard in search as a literal', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->create(['title' => 'Real 100% effort']);
    Todo::factory()->for($user)->create(['title' => 'Unrelated chore']);

    // "%" must match the literal percent, not act as a wildcard returning all.
    $results = app(TodoListQuery::class)
        ->filtered($user, new TodoFilters(search: '100%'))
        ->get();

    expect($results->pluck('title')->all())->toBe(['Real 100% effort']);
});

/*
|--------------------------------------------------------------------------
| Due-date buckets — exclude completed/archived
|--------------------------------------------------------------------------
*/

it('buckets tasks into today, overdue, and upcoming (active only)', function () {
    $user = User::factory()->create();
    $today = Todo::factory()->for($user)->dueOn()->create(['title' => 'Due today']);
    $overdue = Todo::factory()->for($user)->overdue()->create(['title' => 'Overdue']);
    $upcoming = Todo::factory()->for($user)->upcoming()->create(['title' => 'Upcoming']);
    // A completed task that was overdue must not show as overdue.
    Todo::factory()->for($user)->overdue()->completed()->create(['title' => 'Done but late']);

    $query = app(TodoListQuery::class);

    expect($query->filtered($user, new TodoFilters(due: 'today'))->pluck('title')->all())->toBe(['Due today'])
        ->and($query->filtered($user, new TodoFilters(due: 'overdue'))->pluck('title')->all())->toBe(['Overdue'])
        ->and($query->filtered($user, new TodoFilters(due: 'upcoming'))->pluck('title')->all())->toBe(['Upcoming']);

    expect($today->isDueToday())->toBeTrue()
        ->and($overdue->isOverdue())->toBeTrue()
        ->and($upcoming->isOverdue())->toBeFalse();
});

it('supports with and without due date filters for active tasks', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->dueOn()->create(['title' => 'Planned']);
    Todo::factory()->for($user)->create(['title' => 'Unplanned']);

    Livewire::actingAs($user)->test(Index::class)
        ->set('due', 'with')
        ->assertSee('Planned')
        ->assertDontSee('Unplanned')
        ->set('due', 'without')
        ->assertSee('Unplanned')
        ->assertDontSee('Planned');
});

it('ignores due bucket query parameters outside the active tab', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->overdue()->create(['title' => 'Active late']);
    Todo::factory()->for($user)->overdue()->completed()->create(['title' => 'Completed late']);

    Livewire::withQueryParams(['tab' => 'completed', 'due' => 'overdue'])
        ->actingAs($user)
        ->test(Index::class)
        ->assertSee('Completed late')
        ->assertDontSee('Active late');
});

it('does not count completed or archived tasks as overdue in the summary', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->overdue()->create();
    Todo::factory()->for($user)->overdue()->completed()->create();
    Todo::factory()->for($user)->overdue()->archived()->create();

    expect(app(TodoListQuery::class)->summaryFor($user)['overdue'])->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Sorting — validated, safe
|--------------------------------------------------------------------------
*/

it('sorts by priority weight, most important first', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->priority(Priority::Low)->create(['title' => 'Low']);
    Todo::factory()->for($user)->priority(Priority::Urgent)->create(['title' => 'Urgent']);
    Todo::factory()->for($user)->priority(Priority::Normal)->create(['title' => 'Normal']);

    $titles = app(TodoListQuery::class)
        ->filtered($user, new TodoFilters(sort: 'priority', direction: 'desc'))
        ->pluck('title')->all();

    expect($titles)->toBe(['Urgent', 'Normal', 'Low']);
});

it('sorts by project name with tasks without a project last', function () {
    $user = User::factory()->create();
    $beta = Project::factory()->for($user)->create(['name' => 'Beta']);
    $alpha = Project::factory()->for($user)->create(['name' => 'Alpha']);

    Todo::factory()->for($user)->create(['title' => 'Loose']);
    Todo::factory()->for($user)->for($beta)->create(['title' => 'Beta task']);
    Todo::factory()->for($user)->for($alpha)->create(['title' => 'Alpha task']);

    $titles = app(TodoListQuery::class)
        ->filtered($user, new TodoFilters(sort: 'project', direction: 'asc'))
        ->pluck('title')->all();

    expect($titles)->toBe(['Alpha task', 'Beta task', 'Loose']);
});

it('falls back to a safe sort for an unknown sort key', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->count(2)->create();

    // Tampered sort value must not throw or inject SQL — it falls back to created.
    Livewire::actingAs($user)->test(Index::class)
        ->set('sort', 'title); drop table todos;--')
        ->assertOk();

    expect(Todo::query()->count())->toBe(2);
});

/*
|--------------------------------------------------------------------------
| Bulk actions — owner-scoped, lifecycle-aware
|--------------------------------------------------------------------------
*/

it('bulk completes selected active tasks owned by the user', function () {
    $owner = User::factory()->create();
    $a = Todo::factory()->for($owner)->create();
    $b = Todo::factory()->for($owner)->create();

    Livewire::actingAs($owner)->test(Index::class)
        ->set('selected', [$a->id, $b->id])
        ->call('bulkComplete')
        ->assertHasNoErrors();

    expect($a->fresh()->is_completed)->toBeTrue()
        ->and($b->fresh()->is_completed)->toBeTrue();
});

it('bulk archive archives selected tasks owned by the user', function () {
    $owner = User::factory()->create();
    $own = Todo::factory()->for($owner)->create();

    Livewire::actingAs($owner)->test(Index::class)
        ->set('selected', [$own->id])
        ->call('bulkArchive')
        ->assertHasNoErrors();

    expect($own->fresh()->isArchived())->toBeTrue();
});

it('bulk unarchive unarchives selected archived tasks owned by the user', function () {
    $owner = User::factory()->create();
    $own = Todo::factory()->for($owner)->archived()->create();

    Livewire::actingAs($owner)->test(Index::class)
        ->set('selected', [$own->id])
        ->call('bulkUnarchive')
        ->assertHasNoErrors();

    expect($own->fresh()->isArchived())->toBeFalse();
});

it('bulk move sends selected tasks to an owned active project', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner)->create();
    $own = Todo::factory()->for($owner)->create();

    Livewire::actingAs($owner)->test(Index::class)
        ->set('selected', [$own->id])
        ->set('bulkProject', (string) $project->id)
        ->call('bulkMove')
        ->assertHasNoErrors();

    expect($own->fresh()->project_id)->toBe($project->id);
});

it('bulk move rejects a target project owned by another user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $todo = Todo::factory()->for($owner)->create();
    $foreignProject = Project::factory()->for($intruder)->create();

    Livewire::actingAs($owner)->test(Index::class)
        ->set('selected', [$todo->id])
        ->set('bulkProject', (string) $foreignProject->id)
        ->call('bulkMove')
        ->assertHasErrors(['bulkProject']);

    expect($todo->fresh()->project_id)->toBeNull();
});

it('bulk move rejects an archived target project', function () {
    $owner = User::factory()->create();
    $todo = Todo::factory()->for($owner)->create();
    $archivedProject = Project::factory()->for($owner)->archived()->create();

    Livewire::actingAs($owner)->test(Index::class)
        ->set('selected', [$todo->id])
        ->set('bulkProject', (string) $archivedProject->id)
        ->call('bulkMove')
        ->assertHasErrors(['bulkProject']);

    expect($todo->fresh()->project_id)->toBeNull();
});

it('bulk actions reject invalid selected ids before mutating tasks', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();

    Livewire::actingAs($user)->test(Index::class)
        ->set('selected', ['not-an-id'])
        ->call('bulkComplete')
        ->assertHasErrors(['selected.0' => 'integer']);

    expect($todo->fresh()->is_completed)->toBeFalse();
});

it('bulk actions reject selected tasks owned by another user before mutating tasks', function (string $method) {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $own = Todo::factory()->for($owner)->create();
    $foreign = Todo::factory()->for($intruder)->create();

    Livewire::actingAs($owner)->test(Index::class)
        ->set('selected', [$own->id, $foreign->id])
        ->call($method)
        ->assertHasErrors(['selected.1']);

    expect($own->fresh()->is_completed)->toBeFalse()
        ->and($own->isArchived())->toBeFalse()
        ->and(Todo::query()->find($own->id))->not->toBeNull()
        ->and(Todo::query()->find($foreign->id))->not->toBeNull();
})->with([
    'bulkComplete',
    'bulkArchive',
    'bulkUnarchive',
    'bulkDelete',
]);

it('bulk move rejects selected tasks owned by another user before mutating tasks', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $project = Project::factory()->for($owner)->create();
    $own = Todo::factory()->for($owner)->create();
    $foreign = Todo::factory()->for($intruder)->create();

    Livewire::actingAs($owner)->test(Index::class)
        ->set('selected', [$own->id, $foreign->id])
        ->set('bulkProject', (string) $project->id)
        ->call('bulkMove')
        ->assertHasErrors(['selected.1']);

    expect($own->fresh()->project_id)->toBeNull()
        ->and($foreign->fresh()->project_id)->toBeNull();
});

it('bulk delete soft-deletes selected tasks owned by the user', function () {
    $owner = User::factory()->create();
    $own = Todo::factory()->for($owner)->create();

    Livewire::actingAs($owner)->test(Index::class)
        ->set('selected', [$own->id])
        ->call('bulkDelete')
        ->assertHasNoErrors();

    expect(Todo::query()->find($own->id))->toBeNull();
});

it('shows the correct empty state per tab via filtering', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(Index::class)
        ->assertSee(__('todos.empty.active.title'))
        ->set('tab', TodoStatus::Archived->value)
        ->assertSee(__('todos.empty.archived.title'));
});

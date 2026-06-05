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

it('ignores a project that belongs to another user', function () {
    $user = User::factory()->create();
    $foreignProject = Project::factory()->create();

    Livewire::actingAs($user)->test(Index::class)
        ->set('form.title', 'Hijack attempt')
        ->set('form.project_id', (string) $foreignProject->id)
        ->call('createTodo');

    expect($user->todos()->firstWhere('title', 'Hijack attempt')->project_id)->toBeNull();
});

it('ignores tags that belong to another user', function () {
    $user = User::factory()->create();
    $foreignTag = Tag::factory()->create();

    Livewire::actingAs($user)->test(Index::class)
        ->set('form.title', 'Tag hijack')
        ->set('form.tag_ids', [$foreignTag->id])
        ->call('createTodo');

    expect($user->todos()->firstWhere('title', 'Tag hijack')->tags)->toHaveCount(0);
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

it('bulk completes only the users own active selected tasks', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $a = Todo::factory()->for($owner)->create();
    $b = Todo::factory()->for($owner)->create();
    $foreign = Todo::factory()->for($intruder)->create();

    Livewire::actingAs($owner)->test(Index::class)
        ->set('selected', [$a->id, $b->id, $foreign->id])
        ->call('bulkComplete');

    expect($a->fresh()->is_completed)->toBeTrue()
        ->and($b->fresh()->is_completed)->toBeTrue()
        ->and($foreign->fresh()->is_completed)->toBeFalse();
});

it('bulk archive ignores foreign ids', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $own = Todo::factory()->for($owner)->create();
    $foreign = Todo::factory()->for($intruder)->create();

    Livewire::actingAs($owner)->test(Index::class)
        ->set('selected', [$own->id, $foreign->id])
        ->call('bulkArchive');

    expect($own->fresh()->isArchived())->toBeTrue()
        ->and($foreign->fresh()->isArchived())->toBeFalse();
});

it('bulk delete only soft-deletes the users own selected tasks', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $own = Todo::factory()->for($owner)->create();
    $foreign = Todo::factory()->for($intruder)->create();

    Livewire::actingAs($owner)->test(Index::class)
        ->set('selected', [$own->id, $foreign->id])
        ->call('bulkDelete');

    expect(Todo::query()->find($own->id))->toBeNull()
        ->and(Todo::query()->find($foreign->id))->not->toBeNull();
});

it('shows the correct empty state per tab via filtering', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(Index::class)
        ->assertSee(__('todos.empty.active.title'))
        ->set('tab', TodoStatus::Archived->value)
        ->assertSee(__('todos.empty.archived.title'));
});

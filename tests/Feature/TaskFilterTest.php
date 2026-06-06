<?php

use App\Enums\Priority;
use App\Livewire\Todos\Index;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('attribute filters compose without leaking another workspace', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-10 09:30:00', config('app.timezone')));

    try {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $project = Project::factory()->for($owner)->create(['name' => 'Work']);
        $otherProject = Project::factory()->for($owner)->create(['name' => 'Home']);
        $tag = Tag::factory()->for($owner)->create(['name' => 'focus']);
        $otherTag = Tag::factory()->for($owner)->create(['name' => 'later']);

        $match = Todo::factory()
            ->forProject($project)
            ->withTags($tag)
            ->priority(Priority::Urgent)
            ->dueOn('2026-03-11')
            ->create(['title' => 'Filtered private match']);

        Todo::factory()->forProject($otherProject)->withTags($tag)->priority(Priority::Urgent)->dueOn('2026-03-11')->create(['title' => 'Wrong project']);
        Todo::factory()->forProject($project)->withTags($otherTag)->priority(Priority::Urgent)->dueOn('2026-03-11')->create(['title' => 'Wrong tag']);
        Todo::factory()->forProject($project)->withTags($tag)->priority(Priority::Low)->dueOn('2026-03-11')->create(['title' => 'Wrong priority']);
        Todo::factory()->forProject($project)->withTags($tag)->priority(Priority::Urgent)->dueOn('2026-03-10')->create(['title' => 'Wrong due bucket']);
        Todo::factory()->for($other)->priority(Priority::Urgent)->dueOn('2026-03-11')->create(['title' => 'Foreign match']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->set('project', (string) $project->id)
            ->set('tag', (string) $tag->id)
            ->set('priorityFilter', Priority::Urgent->value)
            ->set('due', 'upcoming')
            ->assertSee('Filtered private match')
            ->assertSee(route('todos.show', $match), false)
            ->assertSee(__('todos.filters.project_chip', ['project' => 'Work']))
            ->assertSee(__('todos.filters.tag_chip', ['tag' => '#focus']))
            ->assertSee(__('todos.filters.priority_chip', ['priority' => Priority::Urgent->label()]))
            ->assertSee(__('todos.filters.due_chip', ['due' => __('todos.filters.upcoming')]))
            ->assertDontSee('Wrong project')
            ->assertDontSee('Wrong tag')
            ->assertDontSee('Wrong priority')
            ->assertDontSee('Wrong due bucket')
            ->assertDontSee('Foreign match');
    } finally {
        Carbon::setTestNow();
    }
});

test('project filter query string combines with pagination', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['name' => 'Launch']);
    $baseTime = Carbon::parse('2026-03-10 09:00:00', config('app.timezone'));

    for ($index = 1; $index <= 16; $index++) {
        Todo::factory()->forProject($project)->create([
            'title' => sprintf('Project paginated %02d', $index),
            'created_at' => $baseTime->copy()->addSeconds($index),
            'updated_at' => $baseTime->copy()->addSeconds($index),
        ]);
    }

    Todo::factory()->for($user)->create(['title' => 'Outside project']);

    $this->actingAs($user)
        ->get(route('todos.index', ['project' => (string) $project->id, 'page' => 2]))
        ->assertOk()
        ->assertSee('Project paginated 01')
        ->assertSee(__('todos.filters.project_chip', ['project' => 'Launch']))
        ->assertDontSee('Outside project')
        ->assertDontSee('Project paginated 16');
});

test('invalid priority and due filters return an empty filtered result', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->priority(Priority::Urgent)->dueOn()->create(['title' => 'Visible without bad filters']);

    Livewire::withQueryParams(['priorityFilter' => 'apocalyptic'])
        ->actingAs($user)
        ->test(Index::class)
        ->assertSee(__('todos.empty.filtered.title'))
        ->assertSee(__('todos.empty.filtered.description'))
        ->assertSee(__('todos.filters.priority_chip', ['priority' => __('todos.filters.unavailable_filter')]))
        ->assertDontSee('Visible without bad filters');

    Livewire::withQueryParams(['due' => 'not-a-date-bucket'])
        ->actingAs($user)
        ->test(Index::class)
        ->assertSee(__('todos.empty.filtered.title'))
        ->assertSee(__('todos.filters.due_chip', ['due' => __('todos.filters.unavailable_filter')]))
        ->assertDontSee('Visible without bad filters');
});

test('invalid lifecycle tab state does not expose active tasks', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->create(['title' => 'Active private task']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('tab', 'not-a-status')
        ->assertSee(__('todos.empty.filtered.title'))
        ->assertDontSee('Active private task');
});

test('reset clears attribute filters, chips, pagination state, and selection', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['name' => 'Ops']);
    $tag = Tag::factory()->for($user)->create(['name' => 'triage']);
    $todo = Todo::factory()->forProject($project)->withTags($tag)->priority(Priority::High)->upcoming()->create(['title' => 'Filtered task']);
    Todo::factory()->for($user)->create(['title' => 'Unfiltered task']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('selected', [$todo->id])
        ->set('project', (string) $project->id)
        ->assertSet('selected', [])
        ->set('tag', (string) $tag->id)
        ->set('priorityFilter', Priority::High->value)
        ->set('due', 'upcoming')
        ->assertSee(__('todos.filters.active'))
        ->call('gotoPage', 2)
        ->call('resetFilters')
        ->assertSet('project', '')
        ->assertSet('tag', '')
        ->assertSet('priorityFilter', '')
        ->assertSet('due', '')
        ->assertSet('selected', [])
        ->assertDontSee(__('todos.filters.active'))
        ->assertSee('Filtered task')
        ->assertSee('Unfiltered task');
});

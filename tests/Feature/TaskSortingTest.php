<?php

use App\Enums\Priority;
use App\Livewire\Todos\Index;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Todos\TodoFilters;
use App\Queries\Todos\TodoListQuery;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('title sorting supports both directions without leaking another workspace', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    Todo::factory()->for($owner)->create(['title' => 'Bravo private']);
    Todo::factory()->for($owner)->create(['title' => 'Alpha private']);
    Todo::factory()->for($owner)->create(['title' => 'Charlie private']);
    Todo::factory()->for($other)->create(['title' => 'Aardvark foreign']);

    $query = app(TodoListQuery::class);

    expect($query->filtered($owner, new TodoFilters(sort: 'title', direction: 'asc'))->pluck('title')->all())
        ->toBe(['Alpha private', 'Bravo private', 'Charlie private'])
        ->and($query->filtered($owner, new TodoFilters(sort: 'title', direction: 'desc'))->pluck('title')->all())
        ->toBe(['Charlie private', 'Bravo private', 'Alpha private']);
});

test('due date sorting keeps tasks without dates last in both directions', function () {
    $user = User::factory()->create();

    Todo::factory()->for($user)->dueOn('2026-04-12')->create(['title' => 'Later date']);
    Todo::factory()->for($user)->withoutDueDate()->create(['title' => 'No due date']);
    Todo::factory()->for($user)->dueOn('2026-04-10')->create(['title' => 'Earlier date']);

    $query = app(TodoListQuery::class);

    expect($query->filtered($user, new TodoFilters(sort: 'due', direction: 'asc'))->pluck('title')->all())
        ->toBe(['Earlier date', 'Later date', 'No due date'])
        ->and($query->filtered($user, new TodoFilters(sort: 'due', direction: 'desc'))->pluck('title')->all())
        ->toBe(['Later date', 'Earlier date', 'No due date']);
});

test('project and priority sorts stay deterministic inside owned data', function () {
    $user = User::factory()->create();
    $alpha = Project::factory()->for($user)->create(['name' => 'Alpha']);
    $beta = Project::factory()->for($user)->create(['name' => 'Beta']);

    Todo::factory()->for($user)->priority(Priority::Low)->create(['title' => 'Loose low']);
    Todo::factory()->forProject($beta)->priority(Priority::High)->create(['title' => 'Beta high']);
    Todo::factory()->forProject($alpha)->priority(Priority::Urgent)->create(['title' => 'Alpha urgent']);

    $query = app(TodoListQuery::class);

    expect($query->filtered($user, new TodoFilters(sort: 'project', direction: 'asc'))->pluck('title')->all())
        ->toBe(['Alpha urgent', 'Beta high', 'Loose low'])
        ->and($query->filtered($user, new TodoFilters(sort: 'project', direction: 'desc'))->pluck('title')->all())
        ->toBe(['Beta high', 'Alpha urgent', 'Loose low'])
        ->and($query->filtered($user, new TodoFilters(sort: 'priority', direction: 'desc'))->pluck('title')->all())
        ->toBe(['Alpha urgent', 'Beta high', 'Loose low']);
});

test('created sorting has a stable id tie breaker for pagination', function () {
    $user = User::factory()->create();
    $createdAt = Carbon::parse('2026-04-10 09:00:00', config('app.timezone'));

    Todo::factory()->for($user)->create([
        'title' => 'Inserted first',
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);

    Todo::factory()->for($user)->create([
        'title' => 'Inserted second',
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);

    $query = app(TodoListQuery::class);

    expect($query->filtered($user, new TodoFilters(sort: 'created', direction: 'desc'))->pluck('title')->all())
        ->toBe(['Inserted second', 'Inserted first'])
        ->and($query->filtered($user, new TodoFilters(sort: 'created', direction: 'asc'))->pluck('title')->all())
        ->toBe(['Inserted first', 'Inserted second']);
});

test('sort query string combines with pagination and visible chips', function () {
    $user = User::factory()->create();
    $baseTime = Carbon::parse('2026-04-10 09:00:00', config('app.timezone'));

    for ($index = 1; $index <= 16; $index++) {
        Todo::factory()->for($user)->create([
            'title' => sprintf('Sorted paginated %02d', $index),
            'created_at' => $baseTime->copy()->addSeconds(100 - $index),
            'updated_at' => $baseTime->copy()->addSeconds(100 - $index),
        ]);
    }

    $this->actingAs($user)
        ->get(route('todos.index', ['sort' => 'title', 'direction' => 'asc', 'page' => 2]))
        ->assertOk()
        ->assertSee('Sorted paginated 16')
        ->assertSee(__('todos.filters.sort_chip', ['sort' => __('todos.sort.title')]))
        ->assertSee(__('todos.filters.direction_chip', ['direction' => __('todos.sort.asc')]))
        ->assertDontSee('Sorted paginated 01');
});

test('tampered sort and direction fall back safely and reset clears sort state', function () {
    $user = User::factory()->create();

    Todo::factory()->for($user)->create(['title' => 'Safe first']);
    Todo::factory()->for($user)->create(['title' => 'Safe second']);

    Livewire::withQueryParams([
        'sort' => 'title); drop table todos;--',
        'direction' => 'sideways',
    ])
        ->actingAs($user)
        ->test(Index::class)
        ->assertOk()
        ->assertSee(__('todos.filters.sort_chip', ['sort' => __('todos.filters.unavailable_filter')]))
        ->assertSee(__('todos.filters.direction_chip', ['direction' => __('todos.filters.unavailable_filter')]))
        ->call('resetFilters')
        ->assertSet('sort', 'created')
        ->assertSet('direction', 'desc')
        ->assertDontSee(__('todos.filters.sort_chip', ['sort' => __('todos.filters.unavailable_filter')]))
        ->assertDontSee(__('todos.filters.direction_chip', ['direction' => __('todos.filters.unavailable_filter')]));

    expect(Todo::query()->count())->toBe(2);
});

<?php

use App\Livewire\Todos\Index;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Todos\TodoFilters;
use App\Queries\Todos\TodoListQuery;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('search is owner scoped and treats wildcard input as literal', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    Todo::factory()->for($owner)->create(['title' => 'Save 100% private match']);
    Todo::factory()->for($owner)->create(['title' => 'Save 1000 private miss']);
    Todo::factory()->for($other)->create(['title' => 'Save 100% foreign match']);

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->set('search', '  100%  ')
        ->assertSee('Save 100% private match')
        ->assertSee(__('todos.filters.active'))
        ->assertSee(__('todos.filters.search_chip', ['term' => '100%']))
        ->assertDontSee('Save 1000 private miss')
        ->assertDontSee('Save 100% foreign match');

    expect(app(TodoListQuery::class)
        ->filtered($owner, new TodoFilters(search: '100%'))
        ->pluck('title')
        ->all())->toBe(['Save 100% private match']);
});

test('search query string combines with pagination', function () {
    $user = User::factory()->create();
    $baseTime = Carbon::parse('2026-03-10 09:00:00', config('app.timezone'));

    for ($index = 1; $index <= 16; $index++) {
        Todo::factory()->for($user)->create([
            'title' => sprintf('Alpha paginated %02d', $index),
            'created_at' => $baseTime->copy()->addSeconds($index),
            'updated_at' => $baseTime->copy()->addSeconds($index),
        ]);
    }

    Todo::factory()->for($user)->create(['title' => 'Outside task']);

    $this->actingAs($user)
        ->get(route('todos.index', ['search' => 'Alpha', 'page' => 2]))
        ->assertOk()
        ->assertSee('Alpha paginated 01')
        ->assertSee(__('todos.filters.search_chip', ['term' => 'Alpha']))
        ->assertDontSee('Outside task')
        ->assertDontSee('Alpha paginated 16');
});

test('search reset clears active chips and selected tasks', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['title' => 'Alpha reset task']);
    Todo::factory()->for($user)->create(['title' => 'Visible after reset']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('selected', [$todo->id])
        ->set('search', 'Alpha')
        ->assertSet('selected', [])
        ->assertSee(__('todos.filters.search_chip', ['term' => 'Alpha']))
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertDontSee(__('todos.filters.active'))
        ->assertSee('Visible after reset');
});

test('empty search results use the translated filtered empty state', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->create(['title' => 'Inbox task']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('search', 'No match')
        ->assertSee(__('todos.empty.search.title'))
        ->assertSee(__('todos.empty.filtered.description'))
        ->assertDontSee('Inbox task');
});

test('unexpected project and tag query parameters do not widen searched results', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->create(['title' => 'Alpha protected result']);

    Livewire::withQueryParams([
        'search' => 'Alpha',
        'project' => 'not-a-project-id',
        'tag' => 'not-a-tag-id',
        'sort' => 'title); drop table todos;--',
        'direction' => 'sideways',
    ])
        ->actingAs($user)
        ->test(Index::class)
        ->assertOk()
        ->assertSee(__('todos.empty.search.title'))
        ->assertDontSee('Alpha protected result');

    expect(Todo::query()->count())->toBe(1);
});

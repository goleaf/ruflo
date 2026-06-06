<?php

use App\Data\Todos\SavedTodoViewData;
use App\Enums\Priority;
use App\Livewire\Todos\Index;
use App\Models\Project;
use App\Models\SavedTodoView;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('users can save the current filtered and sorted task view', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['name' => 'Launch']);
    $tag = Tag::factory()->for($user)->create(['name' => 'focus']);
    $todo = Todo::factory()->forProject($project)->withTags($tag)->priority(Priority::Urgent)->upcoming()->create([
        'title' => 'Alpha launch task',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('search', ' Alpha ')
        ->set('project', (string) $project->id)
        ->set('tag', (string) $tag->id)
        ->set('priorityFilter', Priority::Urgent->value)
        ->set('due', 'upcoming')
        ->set('sort', 'title')
        ->set('direction', 'asc')
        ->set('savedViewName', '  Launch focus  ')
        ->call('saveCurrentView')
        ->assertHasNoErrors()
        ->assertSet('savedViewName', '')
        ->assertSee('Launch focus')
        ->assertSee('Alpha launch task');

    $savedView = SavedTodoView::query()->sole();

    expect($savedView->isOwnedBy($user))->toBeTrue()
        ->and($savedView->name)->toBe('Launch focus')
        ->and($savedView->criteria)->toBe([
            'tab' => 'active',
            'search' => 'Alpha',
            'project' => (string) $project->id,
            'tag' => (string) $tag->id,
            'priorityFilter' => Priority::Urgent->value,
            'due' => 'upcoming',
            'sort' => 'title',
            'direction' => 'asc',
        ])
        ->and($todo->exists)->toBeTrue();
});

test('applying a saved view restores URL-backed state and resets pagination and selection', function () {
    $user = User::factory()->create();
    $baseTime = Carbon::parse('2026-05-01 09:00:00', config('app.timezone'));
    $selected = null;

    for ($index = 1; $index <= 16; $index++) {
        $todo = Todo::factory()->for($user)->create([
            'title' => sprintf('Alpha saved %02d', $index),
            'created_at' => $baseTime->copy()->addSeconds(100 - $index),
            'updated_at' => $baseTime->copy()->addSeconds(100 - $index),
        ]);

        $selected ??= $todo;
    }

    Todo::factory()->for($user)->create(['title' => 'Outside saved view']);

    $savedView = SavedTodoView::factory()->for($user)->create([
        'name' => 'Alpha title order',
        'criteria' => SavedTodoViewData::normalizeCriteria([
            'search' => 'Alpha saved',
            'sort' => 'title',
            'direction' => 'asc',
        ]),
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('gotoPage', 2)
        ->set('selected', [$selected->id])
        ->call('applySavedView', $savedView->id)
        ->assertSet('search', 'Alpha saved')
        ->assertSet('sort', 'title')
        ->assertSet('direction', 'asc')
        ->assertSet('selected', [])
        ->assertSee('Alpha saved 01')
        ->assertSee(__('todos.filters.search_chip', ['term' => 'Alpha saved']))
        ->assertSee(__('todos.filters.sort_chip', ['sort' => __('todos.sort.title')]))
        ->assertSee(__('todos.filters.direction_chip', ['direction' => __('todos.sort.asc')]))
        ->assertDontSee('Alpha saved 16')
        ->assertDontSee('Outside saved view');
});

test('blank and duplicate saved view names are rejected with translated validation', function () {
    $user = User::factory()->create();
    SavedTodoView::factory()->for($user)->create(['name' => 'Morning plan']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('savedViewName', '   ')
        ->call('saveCurrentView')
        ->assertHasErrors(['savedViewName']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('savedViewName', 'Morning plan')
        ->call('saveCurrentView')
        ->assertHasErrors(['savedViewName']);

    expect($user->savedTodoViews()->count())->toBe(1);
});

test('saved views are private and foreign ids cannot be applied or deleted', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $ownView = SavedTodoView::factory()->for($owner)->create(['name' => 'Owner view']);
    $foreignView = SavedTodoView::factory()->for($other)->create(['name' => 'Foreign view']);

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->assertSee('Owner view')
        ->assertDontSee('Foreign view');

    expect(fn () => Livewire::actingAs($owner)
        ->test(Index::class)
        ->call('applySavedView', $foreignView->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => Livewire::actingAs($owner)
        ->test(Index::class)
        ->call('deleteSavedView', $foreignView->id))
        ->toThrow(ModelNotFoundException::class);

    expect($ownView->exists)->toBeTrue()
        ->and($foreignView->fresh())->not->toBeNull();
});

test('stale saved project criteria does not leak foreign project names or widen results', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $foreignProject = Project::factory()->for($other)->create(['name' => 'Foreign roadmap']);

    Todo::factory()->for($owner)->create(['title' => 'Owner visible task']);

    $savedView = SavedTodoView::factory()->for($owner)->create([
        'name' => 'Stale project',
        'criteria' => SavedTodoViewData::normalizeCriteria([
            'project' => (string) $foreignProject->id,
        ]),
    ]);

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->call('applySavedView', $savedView->id)
        ->assertSet('project', (string) $foreignProject->id)
        ->assertSee(__('todos.empty.project.title'))
        ->assertSee(__('todos.filters.project_chip', ['project' => __('todos.filters.unavailable_filter')]))
        ->assertDontSee('Owner visible task')
        ->assertDontSee('Foreign roadmap');
});

test('owners can delete their saved views', function () {
    $user = User::factory()->create();
    $savedView = SavedTodoView::factory()->for($user)->create(['name' => 'Delete me']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Delete me')
        ->call('deleteSavedView', $savedView->id)
        ->assertDontSee('Delete me');

    expect(SavedTodoView::query()->whereKey($savedView->id)->exists())->toBeFalse();
});

test('saved view criteria normalization strips unsafe values', function () {
    expect(SavedTodoViewData::normalizeCriteria([
        'tab' => 'not-a-tab',
        'search' => str_repeat('x', 130),
        'project' => 'not-a-project',
        'tag' => 'not-a-tag',
        'priorityFilter' => 'apocalyptic',
        'due' => 'tomorrow-ish',
        'sort' => 'title); drop table todos;--',
        'direction' => 'sideways',
    ]))->toBe([
        'tab' => 'active',
        'search' => str_repeat('x', 120),
        'project' => '',
        'tag' => '',
        'priorityFilter' => '',
        'due' => '',
        'sort' => 'created',
        'direction' => 'desc',
    ]);
});

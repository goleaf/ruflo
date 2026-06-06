<?php

use App\Actions\Todos\RescheduleFocusedTodo;
use App\Enums\Priority;
use App\Exceptions\InvalidTodoTransition;
use App\Livewire\Todos\Focus;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

function focusRouteMiddleware(string $routeName): array
{
    return Route::getRoutes()->getByName($routeName)?->gatherMiddleware() ?? [];
}

test('focus route redirects guests and unverified users', function () {
    $this->get(route('todos.focus'))->assertRedirect(route('login'));

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('todos.focus'))
        ->assertRedirect(route('verification.notice'));
});

test('focus mode renders only owner scoped important active tasks', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-10 09:30:00', config('app.timezone')));

    try {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $project = Project::factory()->for($user)->create(['name' => 'Launch']);
        $tag = Tag::factory()->for($user)->create(['name' => 'focus']);

        $urgent = Todo::factory()
            ->forProject($project)
            ->withTags($tag)
            ->urgentPriority()
            ->create(['title' => 'Handle urgent launch']);
        Todo::factory()->for($user)->highPriority()->create(['title' => 'Shape high priority']);
        Todo::factory()->for($user)->overdue()->create(['title' => 'Resolve overdue']);
        Todo::factory()->for($user)->dueOn('2026-03-10')->create(['title' => 'Finish today']);
        Todo::factory()->for($user)->create(['title' => 'Normal no date']);
        Todo::factory()->for($user)->focusCandidate()->completed()->create(['title' => 'Completed focus']);
        Todo::factory()->for($user)->urgentPriority()->archived()->create(['title' => 'Archived urgent']);
        Todo::factory()->for($other)->urgentPriority()->create(['title' => 'Foreign urgent']);

        $this->actingAs($user)
            ->get(route('todos.focus'))
            ->assertOk()
            ->assertSee(__('todos.pages.focus.title'))
            ->assertSee('Handle urgent launch')
            ->assertSee('Shape high priority')
            ->assertSee('Resolve overdue')
            ->assertSee('Finish today')
            ->assertSee('Launch')
            ->assertSee('#focus')
            ->assertSee(Priority::Urgent->label())
            ->assertSee(route('todos.show', $urgent), false)
            ->assertDontSee('Normal no date')
            ->assertDontSee('Completed focus')
            ->assertDontSee('Archived urgent')
            ->assertDontSee('Foreign urgent');
    } finally {
        Carbon::setTestNow();
    }
});

test('focus mode includes all urgent tasks before filling normal slots', function () {
    $user = User::factory()->create();

    foreach (range(1, 6) as $index) {
        Todo::factory()->for($user)->urgentPriority()->create(['title' => 'Urgent focus '.$index]);
    }

    Todo::factory()->for($user)->focusCandidate()->create(['title' => 'High fill task']);

    $response = $this->actingAs($user)->get(route('todos.focus'));

    $response->assertOk();

    foreach (range(1, 6) as $index) {
        $response->assertSee('Urgent focus '.$index);
    }

    $response->assertDontSee('High fill task');
});

test('focus quick actions complete defer and snooze selected tasks', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-10 09:30:00', config('app.timezone')));

    try {
        $user = User::factory()->create();
        $urgent = Todo::factory()->for($user)->urgentPriority()->dueOn('2026-03-10')->create(['title' => 'Urgent now']);
        $high = Todo::factory()->for($user)->focusCandidate()->create(['title' => 'High today']);

        Livewire::actingAs($user)
            ->test(Focus::class)
            ->call('deferTodo', $urgent->id)
            ->assertHasNoErrors()
            ->call('selectTask', $high->id)
            ->assertSet('selectedTodoId', $high->id)
            ->call('snoozeSelected')
            ->assertHasNoErrors()
            ->call('selectTask', $high->id)
            ->call('completeSelected')
            ->assertHasNoErrors();

        $urgent->refresh();
        $high->refresh();

        expect($urgent->due_date->toDateString())->toBe('2026-03-11')
            ->and($urgent->priority)->toBe(Priority::Urgent)
            ->and($urgent->is_completed)->toBeFalse()
            ->and($high->due_date->toDateString())->toBe('2026-03-13')
            ->and($high->is_completed)->toBeTrue();
    } finally {
        Carbon::setTestNow();
    }
});

test('foreign and non focus tasks cannot be changed from focus mode', function () {
    $viewer = User::factory()->create();
    $owner = User::factory()->create();
    $foreignTodo = Todo::factory()->for($owner)->urgentPriority()->create();
    $notFocus = Todo::factory()->for($viewer)->create();
    $archived = Todo::factory()->for($viewer)->urgentPriority()->archived()->create();

    expect(fn () => Livewire::actingAs($viewer)
        ->test(Focus::class)
        ->call('completeTodo', $foreignTodo->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => Livewire::actingAs($viewer)
        ->test(Focus::class)
        ->call('deferTodo', $notFocus->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => app(RescheduleFocusedTodo::class)->defer($viewer, $archived))
        ->toThrow(InvalidTodoTransition::class);
});

test('focus route and component keep private view guardrails', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Focus.php'));
    $view = file_get_contents(resource_path('views/livewire/todos/focus.blade.php'));

    expect(focusRouteMiddleware('todos.focus'))
        ->toContain('auth', 'verified')
        ->and(route('todos.focus'))->toBe('https://ruflo.test/todos/focus')
        ->and($source)
        ->toContain('TodoFocusQuery')
        ->toContain('RescheduleFocusedTodo')
        ->toContain('CompleteTodo')
        ->toContain('$this->authorize')
        ->not->toContain('Todo::query()')
        ->not->toContain('->save()')
        ->and($view)
        ->toContain('x-on:keydown.window')
        ->toContain('kbd="C"')
        ->toContain('kbd="D"')
        ->toContain('kbd="S"')
        ->not->toContain('@php');
});

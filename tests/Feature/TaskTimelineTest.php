<?php

use App\Livewire\Todos\TaskTimeline;
use App\Models\ActivityRecord;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

test('task detail renders owner scoped task timeline activity', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['title' => 'Timeline-visible task']);

    ActivityRecord::factory()->todoCreated($todo)->create(['occurred_at' => now()->subMinutes(3)]);
    ActivityRecord::factory()
        ->todoUpdated($todo)
        ->withChanges(['title' => ['old' => 'Old task title', 'new' => 'Timeline-visible task']])
        ->create(['occurred_at' => now()->subMinutes(2)]);
    ActivityRecord::factory()->forUser($user)->create([
        'event' => 'todo.updated',
        'subject_type' => (new Todo)->getMorphClass(),
        'subject_id' => $todo->id + 1000,
        'subject_title' => 'Unrelated activity record',
    ]);
    ActivityRecord::factory()->forUser($other)->create([
        'event' => 'todo.updated',
        'subject_type' => (new Todo)->getMorphClass(),
        'subject_id' => $todo->id,
        'subject_title' => 'Spoofed foreign activity',
    ]);

    $this->actingAs($user)
        ->get(route('todos.show', $todo))
        ->assertOk()
        ->assertSee('data-test="task-timeline"', false)
        ->assertSeeText(__('activity.task_timeline.heading'))
        ->assertSeeText(__('activity.events.todo.created.label'))
        ->assertSeeText(__('activity.events.todo.updated.label'))
        ->assertSeeText('Timeline-visible task')
        ->assertSeeText(__('activity.changes.summary', ['fields' => __('activity.fields.title')]))
        ->assertDontSeeText('Unrelated activity record')
        ->assertDontSeeText('Spoofed foreign activity');
});

test('task timeline loads more activity records without stale subject links', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['title' => 'Timeline pagination task']);

    ActivityRecord::factory()
        ->count(6)
        ->forTodo($todo)
        ->sequence(fn (Sequence $sequence): array => [
            'event' => $sequence->index === 0 ? 'todo.deleted' : 'todo.updated',
            'subject_title' => 'Task timeline item '.($sequence->index + 1),
            'occurred_at' => now()->subMinutes($sequence->index + 1),
        ])
        ->create();

    Livewire::actingAs($user)
        ->test(TaskTimeline::class, ['todoId' => $todo->id])
        ->assertSee(__('activity.actions.load_more'))
        ->assertSee('Task timeline item 1')
        ->assertDontSee('Task timeline item 6')
        ->assertDontSee('activity-subject-link', false)
        ->call('loadMore')
        ->assertSet('limit', 10)
        ->assertSee('Task timeline item 6');
});

test('task timeline falls back to safe deleted labels when snapshots are missing', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['title' => 'Visible restored task']);

    ActivityRecord::factory()
        ->todoDeleted($todo)
        ->create([
            'subject_title' => null,
            'occurred_at' => now(),
        ]);

    Livewire::actingAs($user)
        ->test(TaskTimeline::class, ['todoId' => $todo->id])
        ->assertSee(__('activity.events.todo.deleted.label'))
        ->assertSee(__('activity.subjects.deleted'))
        ->assertDontSee('activity-subject-link', false);
});

test('task timeline rejects foreign task ids as not found', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();

    expect(fn () => Livewire::actingAs($other)->test(TaskTimeline::class, ['todoId' => $todo->id]))
        ->toThrow(ModelNotFoundException::class);
});

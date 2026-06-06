<?php

use App\Actions\Todos\CompleteTodo;
use App\Actions\Todos\CreateTodo;
use App\Actions\Todos\DeleteTodo;
use App\Actions\Todos\UpdateTodo;
use App\Data\Todos\TodoData;
use App\Enums\Priority;
use App\Events\TodoDeleted;
use App\Livewire\Activity\Index as ActivityIndex;
use App\Models\ActivityRecord;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use Database\Seeders\ActivityRecordSeeder;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Livewire\Livewire;

test('meaningful todo actions create owner-scoped activity records', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $todo = app(CreateTodo::class)->handle($user, new TodoData(
        title: 'Ship activity history',
        projectId: $project->id,
    ));

    app(UpdateTodo::class)->handle($user, $todo, new TodoData(
        title: 'Ship private activity history',
        priority: Priority::High,
        projectId: $project->id,
    ));
    app(CompleteTodo::class)->handle($todo);
    app(DeleteTodo::class)->handle($todo);

    app(CreateTodo::class)->handle($other, new TodoData(title: 'Foreign activity task'));

    $records = ActivityRecord::query()
        ->ownedBy($user)
        ->orderBy('id')
        ->get();

    expect($records->pluck('event')->all())->toContain('todo.created', 'todo.updated', 'todo.completed', 'todo.deleted')
        ->and(ActivityRecord::query()->ownedBy($other)->count())->toBe(1);

    $updated = $records->firstWhere('event', 'todo.updated');

    expect(data_get($updated->metadata, 'changes.title.old'))->toBe('Ship activity history')
        ->and(data_get($updated->metadata, 'changes.title.new'))->toBe('Ship private activity history')
        ->and(data_get($updated->metadata, 'changes.priority.new'))->toBe(Priority::High->value);

    $this->actingAs($user)
        ->get(route('activity.index'))
        ->assertOk()
        ->assertSeeText(__('activity.pages.index.title'))
        ->assertSeeText('Ship private activity history')
        ->assertDontSeeText('Foreign activity task');
});

test('activity listener skips no-op todo updates', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()
        ->for($user)
        ->normalPriority()
        ->create(['title' => 'Keep the same task']);

    app(UpdateTodo::class)->handle($user, $todo, new TodoData(title: 'Keep the same task'));

    expect(ActivityRecord::query()->where('event', 'todo.updated')->count())->toBe(0);
});

test('activity listener resolves missing titles from partially hydrated todo events', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['title' => 'Bulk selected task']);
    $partialTodo = Todo::query()
        ->select(['id', 'user_id'])
        ->findOrFail($todo->id);

    TodoDeleted::dispatch($partialTodo);

    expect(ActivityRecord::query()->first()?->subject_title)->toBe('Bulk selected task');
});

test('activity page loads more records and hides stale deleted task links', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['title' => 'Hidden deleted task']);
    $deletedActivity = ActivityRecord::factory()->todoDeleted($todo)->create(['occurred_at' => now()->addMinute()]);

    $todo->delete();

    ActivityRecord::factory()
        ->count(11)
        ->forUser($user)
        ->sequence(fn (Sequence $sequence): array => [
            'subject_title' => 'Timeline item '.($sequence->index + 1),
            'occurred_at' => now()->subMinutes($sequence->index + 1),
        ])
        ->create();

    Livewire::actingAs($user)
        ->test(ActivityIndex::class)
        ->assertSee(__('activity.actions.load_more'))
        ->assertSee('Hidden deleted task')
        ->assertDontSee('activity-subject-link-'.$deletedActivity->id, false)
        ->call('loadMore')
        ->assertSee('Timeline item 11')
        ->assertSet('limit', 20);
});

test('activity route requires an authenticated verified user', function () {
    $this->get(route('activity.index'))
        ->assertRedirect(route('login'));
});

test('activity factory and seeder provide local demo history', function () {
    $user = User::factory()->demoPrimary()->create();
    Todo::factory()->for($user)->create(['title' => 'Seeded activity task']);

    $this->seed(ActivityRecordSeeder::class);

    expect(ActivityRecord::query()->ownedBy($user)->count())->toBeGreaterThan(0)
        ->and(ActivityRecord::query()->ownedBy($user)->where('subject_title', 'Seeded activity task')->exists())->toBeTrue();
});

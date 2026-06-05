<?php

use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Models\Concerns\BelongsToUser;
use App\Models\Project;
use App\Models\Reminder;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use App\Policies\ProjectPolicy;
use App\Policies\ReminderPolicy;
use App\Policies\TagPolicy;
use App\Policies\TodoPolicy;
use App\Queries\Dashboard\DailySummaryQuery;
use App\Queries\Todos\TodoFilters;
use App\Queries\Todos\TodoListQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

test('private workspace resources share the owning user boundary', function () {
    $privateModels = [Todo::class, Project::class, Tag::class];

    foreach ($privateModels as $modelClass) {
        /** @var Model $model */
        $model = new $modelClass;

        expect(class_uses_recursive($modelClass))->toContain(BelongsToUser::class)
            ->and($model->getFillable())->not->toContain('user_id');
    }
});

test('private workspace models resolve explicit policies', function () {
    expect(Gate::getPolicyFor(Todo::class))->toBeInstanceOf(TodoPolicy::class)
        ->and(Gate::getPolicyFor(Project::class))->toBeInstanceOf(ProjectPolicy::class)
        ->and(Gate::getPolicyFor(Tag::class))->toBeInstanceOf(TagPolicy::class)
        ->and(Gate::getPolicyFor(Reminder::class))->toBeInstanceOf(ReminderPolicy::class);
});

test('foreign private records are denied as not found', function (string $modelClass) {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $record = $modelClass::factory()->for($owner)->create();

    $response = Gate::forUser($intruder)->inspect('view', $record);

    expect($response->denied())->toBeTrue()
        ->and($response->status())->toBe(404);
})->with([
    'todo' => Todo::class,
    'project' => Project::class,
    'tag' => Tag::class,
]);

test('dashboard summary counts only the authenticated users private workspace', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Todo::factory()->for($user)->create();
    Todo::factory()->for($user)->overdue()->create();
    Todo::factory()->for($user)->completed()->create();
    Todo::factory()->for($user)->archived()->create();
    Project::factory()->for($user)->create();
    Project::factory()->for($user)->archived()->create();
    Tag::factory()->for($user)->create();

    Todo::factory()->for($other)->count(5)->create();
    Project::factory()->for($other)->count(4)->create();
    Tag::factory()->for($other)->count(3)->create();

    expect(app(DailySummaryQuery::class)->for($user))->toBe([
        'active' => 2,
        'completed' => 1,
        'archived' => 1,
        'overdue' => 1,
        'projects' => 1,
        'tags' => 1,
    ]);
});

test('dashboard Livewire component renders the scoped private summary', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->overdue()->create();
    Project::factory()->for($user)->create();
    Tag::factory()->for($user)->create();

    Livewire::actingAs($user)->test(DashboardIndex::class)
        ->assertSee(__('dashboard.heading'))
        ->assertSee(__('dashboard.summary.active'))
        ->assertSee(__('dashboard.summary.projects'))
        ->assertSee(__('dashboard.workspace.action'));
});

test('todo list query never hydrates foreign project or tag labels', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $todo = Todo::factory()->for($owner)->create(['title' => 'Owner task']);
    $foreignProject = Project::factory()->for($other)->create(['name' => 'Foreign project']);
    $ownTag = Tag::factory()->for($owner)->create(['name' => 'mine']);
    $foreignTag = Tag::factory()->for($other)->create(['name' => 'theirs']);

    $todo->forceFill(['project_id' => $foreignProject->id])->save();
    $todo->tags()->attach($ownTag);
    DB::table('tag_todo')->insert([
        'tag_id' => $foreignTag->id,
        'todo_id' => $todo->id,
    ]);

    $visibleTodo = app(TodoListQuery::class)
        ->filtered($owner, new TodoFilters)
        ->firstOrFail();

    expect($visibleTodo->title)->toBe('Owner task')
        ->and($visibleTodo->relationLoaded('project'))->toBeTrue()
        ->and($visibleTodo->project)->toBeNull()
        ->and($visibleTodo->tags->pluck('name')->all())->toBe(['mine']);
});

test('reminder placeholder is inaccessible until it has an owner boundary', function () {
    $user = User::factory()->create();
    $reminder = Reminder::factory()->create();
    $gate = Gate::forUser($user);

    expect($gate->denies('viewAny', Reminder::class))->toBeTrue()
        ->and($gate->denies('create', Reminder::class))->toBeTrue()
        ->and($gate->denies('view', $reminder))->toBeTrue()
        ->and($gate->denies('update', $reminder))->toBeTrue()
        ->and($gate->denies('delete', $reminder))->toBeTrue()
        ->and($gate->denies('restore', $reminder))->toBeTrue()
        ->and($gate->denies('forceDelete', $reminder))->toBeTrue();
});

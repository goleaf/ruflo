<?php

use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Models\Concerns\BelongsToUser;
use App\Models\Goal;
use App\Models\GoalMilestone;
use App\Models\Habit;
use App\Models\HabitCheckIn;
use App\Models\PomodoroSession;
use App\Models\Project;
use App\Models\Reminder;
use App\Models\SavedTodoView;
use App\Models\Tag;
use App\Models\TimeEntry;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\TodoTemplate;
use App\Models\User;
use App\Policies\GoalMilestonePolicy;
use App\Policies\GoalPolicy;
use App\Policies\HabitCheckInPolicy;
use App\Policies\HabitPolicy;
use App\Policies\PomodoroSessionPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\ReminderPolicy;
use App\Policies\SavedTodoViewPolicy;
use App\Policies\TagPolicy;
use App\Policies\TimeEntryPolicy;
use App\Policies\TodoChecklistItemPolicy;
use App\Policies\TodoPolicy;
use App\Policies\TodoTemplatePolicy;
use App\Queries\Dashboard\DailySummaryQuery;
use App\Queries\Todos\TodoFilters;
use App\Queries\Todos\TodoListQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

test('private workspace resources share the owning user boundary', function () {
    $privateModels = [Todo::class, Project::class, Goal::class, GoalMilestone::class, Habit::class, HabitCheckIn::class, PomodoroSession::class, TimeEntry::class, Tag::class, SavedTodoView::class, TodoChecklistItem::class, TodoTemplate::class];

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
        ->and(Gate::getPolicyFor(Goal::class))->toBeInstanceOf(GoalPolicy::class)
        ->and(Gate::getPolicyFor(GoalMilestone::class))->toBeInstanceOf(GoalMilestonePolicy::class)
        ->and(Gate::getPolicyFor(Habit::class))->toBeInstanceOf(HabitPolicy::class)
        ->and(Gate::getPolicyFor(HabitCheckIn::class))->toBeInstanceOf(HabitCheckInPolicy::class)
        ->and(Gate::getPolicyFor(PomodoroSession::class))->toBeInstanceOf(PomodoroSessionPolicy::class)
        ->and(Gate::getPolicyFor(TimeEntry::class))->toBeInstanceOf(TimeEntryPolicy::class)
        ->and(Gate::getPolicyFor(Tag::class))->toBeInstanceOf(TagPolicy::class)
        ->and(Gate::getPolicyFor(TodoChecklistItem::class))->toBeInstanceOf(TodoChecklistItemPolicy::class)
        ->and(Gate::getPolicyFor(TodoTemplate::class))->toBeInstanceOf(TodoTemplatePolicy::class)
        ->and(Gate::getPolicyFor(SavedTodoView::class))->toBeInstanceOf(SavedTodoViewPolicy::class)
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
    'goal' => Goal::class,
    'goal milestone' => GoalMilestone::class,
    'habit' => Habit::class,
    'habit check in' => HabitCheckIn::class,
    'pomodoro session' => PomodoroSession::class,
    'time entry' => TimeEntry::class,
    'tag' => Tag::class,
    'saved todo view' => SavedTodoView::class,
    'todo checklist item' => TodoChecklistItem::class,
    'todo template' => TodoTemplate::class,
]);

test('dashboard summary counts only the authenticated users private workspace', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Todo::factory()->for($user)->create();
    Todo::factory()->for($user)->overdue()->create();
    Todo::factory()->for($user)->completed()->create();
    Todo::factory()->for($user)->archived()->create();
    Todo::factory()->for($user)->deleted()->create();
    Project::factory()->for($user)->create();
    Project::factory()->for($user)->archived()->create();
    $goal = Goal::factory()->for($user)->create();
    GoalMilestone::factory()->forGoal($goal)->create();
    $habit = Habit::factory()->for($user)->create();
    HabitCheckIn::factory()->forHabit($habit)->create();
    Tag::factory()->for($user)->create();

    Todo::factory()->for($other)->count(5)->create();
    Project::factory()->for($other)->count(4)->create();
    Goal::factory()->for($other)->count(3)->create();
    Habit::factory()->for($other)->count(3)->create();
    Tag::factory()->for($other)->count(3)->create();

    expect(app(DailySummaryQuery::class)->for($user))->toBe([
        'active' => 2,
        'completed' => 1,
        'archived' => 1,
        'trash' => 1,
        'overdue' => 1,
        'projects' => 1,
        'tags' => 1,
        'goals' => 1,
        'milestones' => 1,
        'habits' => 1,
        'habit_check_ins' => 1,
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
        ->assertSee(__('dashboard.summary.trash'))
        ->assertSee(__('dashboard.summary.projects'))
        ->assertSee(__('dashboard.summary.goals'))
        ->assertSee(__('dashboard.summary.habits'))
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

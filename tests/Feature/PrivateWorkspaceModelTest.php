<?php

use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Models\AutomationRule;
use App\Models\AutomationRuleRun;
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
use App\Models\TodoDependency;
use App\Models\TodoTemplate;
use App\Models\User;
use App\Notifications\DailySummaryNotification;
use App\Policies\AutomationRulePolicy;
use App\Policies\AutomationRuleRunPolicy;
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
use App\Policies\TodoDependencyPolicy;
use App\Policies\TodoPolicy;
use App\Policies\TodoTemplatePolicy;
use App\Queries\Dashboard\DailyDashboardQuery;
use App\Queries\Dashboard\DailySummaryQuery;
use App\Queries\Todos\TodoFilters;
use App\Queries\Todos\TodoListQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

test('private workspace resources share the owning user boundary', function () {
    $privateModels = [Todo::class, Project::class, Goal::class, GoalMilestone::class, Habit::class, HabitCheckIn::class, PomodoroSession::class, TimeEntry::class, Tag::class, SavedTodoView::class, TodoChecklistItem::class, TodoDependency::class, TodoTemplate::class, AutomationRule::class, AutomationRuleRun::class, Reminder::class];

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
        ->and(Gate::getPolicyFor(TodoDependency::class))->toBeInstanceOf(TodoDependencyPolicy::class)
        ->and(Gate::getPolicyFor(TodoTemplate::class))->toBeInstanceOf(TodoTemplatePolicy::class)
        ->and(Gate::getPolicyFor(SavedTodoView::class))->toBeInstanceOf(SavedTodoViewPolicy::class)
        ->and(Gate::getPolicyFor(AutomationRule::class))->toBeInstanceOf(AutomationRulePolicy::class)
        ->and(Gate::getPolicyFor(AutomationRuleRun::class))->toBeInstanceOf(AutomationRuleRunPolicy::class)
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
    'todo dependency' => TodoDependency::class,
    'todo template' => TodoTemplate::class,
    'automation rule' => AutomationRule::class,
    'automation rule run' => AutomationRuleRun::class,
    'reminder' => Reminder::class,
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
        'blocked' => 0,
        'projects' => 1,
        'tags' => 1,
        'goals' => 1,
        'milestones' => 1,
        'habits' => 1,
        'habit_check_ins' => 1,
    ]);
});

test('daily dashboard widget summary stays owner scoped and excludes inactive tasks', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $dueToday = Todo::factory()->for($user)->dueToday()->create();
    Todo::factory()->for($user)->overdue()->create();
    Todo::factory()->for($user)->upcoming()->create();
    Todo::factory()->for($user)->withoutDueDate()->create();
    Todo::factory()->for($user)->dueOn(today()->addDays(14))->create();
    Todo::factory()->for($user)->dueToday()->completed()->create();
    Todo::factory()->for($user)->dueToday()->archived()->create();
    Todo::factory()->for($user)->dueToday()->deleted()->create();

    $waiting = Todo::factory()->for($user)->dueToday()->create();
    $blocker = Todo::factory()->for($user)->create();
    TodoDependency::factory()->forTodos($waiting, $blocker)->create();

    Reminder::factory()->forTodo($dueToday)->due()->create();
    Reminder::factory()->forTodo($waiting)->future()->create();
    TimeEntry::factory()->for($user)->manual(90)->create(['entry_date' => today()->toDateString()]);
    TimeEntry::factory()->for($user)->running()->create();
    $user->notify(new DailySummaryNotification(dueCount: 2, overdueCount: 1));

    Todo::factory()->for($other)->dueToday()->count(3)->create();
    Todo::factory()->for($other)->overdue()->count(2)->create();
    Reminder::factory()->forTodo(Todo::factory()->for($other)->create())->due()->create();
    TimeEntry::factory()->for($other)->manual(45)->create(['entry_date' => today()->toDateString()]);
    $other->notify(new DailySummaryNotification(dueCount: 9, overdueCount: 9));

    expect(app(DailyDashboardQuery::class)->for($user))->toBe([
        'date' => today()->toDateString(),
        'attention_total' => 6,
        'active_total' => 7,
        'scheduled_total' => 5,
        'schedule_coverage_percent' => 71,
        'due_today' => 2,
        'overdue' => 1,
        'due_soon' => 1,
        'unplanned' => 2,
        'blocked' => 1,
        'due_reminders' => 1,
        'pending_reminders' => 2,
        'unread_notifications' => 1,
        'time_today_seconds' => 5400,
        'active_timer_count' => 1,
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
        ->assertSee(__('dashboard.daily.heading'))
        ->assertSee(__('dashboard.daily.schedule_coverage.label'))
        ->assertSee(__('dashboard.workspace.action'));
});

test('dashboard daily widget details can be collapsed without changing counts', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->dueToday()->create();

    Livewire::actingAs($user)->test(DashboardIndex::class)
        ->assertSet('showDailyDetails', true)
        ->assertSee(__('dashboard.daily.details.planning'))
        ->call('toggleDailyDetails')
        ->assertSet('showDailyDetails', false)
        ->assertSee(__('dashboard.daily.heading'))
        ->assertDontSee(__('dashboard.daily.details.planning'));
});

test('dashboard daily widget renders an empty state for clear accounts', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(DashboardIndex::class)
        ->assertSee(__('dashboard.daily.clear_heading'))
        ->assertSee(__('dashboard.daily.clear_description'));
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

test('reminders keep task links inside the owner boundary', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $todo = Todo::factory()->for($owner)->create(['title' => 'Owner reminder task']);
    $foreignTodo = Todo::factory()->for($intruder)->create(['title' => 'Foreign reminder task']);
    $reminder = Reminder::factory()->forTodo($todo)->create();

    $reminder->forceFill(['todo_id' => $foreignTodo->id])->save();

    $visible = $owner->reminders()
        ->with(['todo' => fn ($query) => $query->where('todos.user_id', $owner->id)])
        ->sole();

    expect($reminder->isOwnedBy($owner))->toBeTrue()
        ->and($visible->todo)->toBeNull()
        ->and(Gate::forUser($owner)->allows('view', $reminder))->toBeTrue()
        ->and(Gate::forUser($intruder)->inspect('view', $reminder)->status())->toBe(404);
});

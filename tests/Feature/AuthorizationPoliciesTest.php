<?php

use App\Models\AutomationRule;
use App\Models\AutomationRuleRun;
use App\Models\Goal;
use App\Models\GoalMilestone;
use App\Models\Habit;
use App\Models\HabitCheckIn;
use App\Models\PomodoroSession;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Reminder;
use App\Models\SavedTodoView;
use App\Models\Tag;
use App\Models\TimeEntry;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\TodoDependency;
use App\Models\TodoRecurrenceException;
use App\Models\TodoRecurrenceRule;
use App\Models\TodoTemplate;
use App\Models\User;
use App\Policies\AutomationRulePolicy;
use App\Policies\AutomationRuleRunPolicy;
use App\Policies\GoalMilestonePolicy;
use App\Policies\GoalPolicy;
use App\Policies\HabitCheckInPolicy;
use App\Policies\HabitPolicy;
use App\Policies\PomodoroSessionPolicy;
use App\Policies\ProjectMembershipPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\ReminderPolicy;
use App\Policies\SavedTodoViewPolicy;
use App\Policies\TagPolicy;
use App\Policies\TimeEntryPolicy;
use App\Policies\TodoChecklistItemPolicy;
use App\Policies\TodoDependencyPolicy;
use App\Policies\TodoPolicy;
use App\Policies\TodoRecurrenceExceptionPolicy;
use App\Policies\TodoTemplatePolicy;
use Illuminate\Support\Facades\Gate;

test('tracked private resources resolve explicit policies', function () {
    expect(Gate::getPolicyFor(Todo::class))->toBeInstanceOf(TodoPolicy::class)
        ->and(Gate::getPolicyFor(Project::class))->toBeInstanceOf(ProjectPolicy::class)
        ->and(Gate::getPolicyFor(ProjectMembership::class))->toBeInstanceOf(ProjectMembershipPolicy::class)
        ->and(Gate::getPolicyFor(Goal::class))->toBeInstanceOf(GoalPolicy::class)
        ->and(Gate::getPolicyFor(GoalMilestone::class))->toBeInstanceOf(GoalMilestonePolicy::class)
        ->and(Gate::getPolicyFor(Habit::class))->toBeInstanceOf(HabitPolicy::class)
        ->and(Gate::getPolicyFor(HabitCheckIn::class))->toBeInstanceOf(HabitCheckInPolicy::class)
        ->and(Gate::getPolicyFor(PomodoroSession::class))->toBeInstanceOf(PomodoroSessionPolicy::class)
        ->and(Gate::getPolicyFor(TimeEntry::class))->toBeInstanceOf(TimeEntryPolicy::class)
        ->and(Gate::getPolicyFor(Tag::class))->toBeInstanceOf(TagPolicy::class)
        ->and(Gate::getPolicyFor(TodoChecklistItem::class))->toBeInstanceOf(TodoChecklistItemPolicy::class)
        ->and(Gate::getPolicyFor(TodoDependency::class))->toBeInstanceOf(TodoDependencyPolicy::class)
        ->and(Gate::getPolicyFor(TodoRecurrenceException::class))->toBeInstanceOf(TodoRecurrenceExceptionPolicy::class)
        ->and(Gate::getPolicyFor(TodoTemplate::class))->toBeInstanceOf(TodoTemplatePolicy::class)
        ->and(Gate::getPolicyFor(SavedTodoView::class))->toBeInstanceOf(SavedTodoViewPolicy::class)
        ->and(Gate::getPolicyFor(AutomationRule::class))->toBeInstanceOf(AutomationRulePolicy::class)
        ->and(Gate::getPolicyFor(AutomationRuleRun::class))->toBeInstanceOf(AutomationRuleRunPolicy::class)
        ->and(Gate::getPolicyFor(Reminder::class))->toBeInstanceOf(ReminderPolicy::class);
});

test('automation rule policy covers owner run abilities', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $rule = AutomationRule::factory()->for($owner)->create();

    $ownerGate = Gate::forUser($owner);
    $intruderGate = Gate::forUser($intruder);

    foreach (['view', 'update', 'run'] as $ability) {
        expect($ownerGate->allows($ability, $rule))->toBeTrue();

        $response = $intruderGate->inspect($ability, $rule);

        expect($response->denied())->toBeTrue()
            ->and($response->status())->toBe(404);
    }

    expect($ownerGate->allows('viewAny', AutomationRule::class))->toBeTrue()
        ->and($ownerGate->allows('create', AutomationRule::class))->toBeTrue()
        ->and($ownerGate->denies('delete', $rule))->toBeTrue()
        ->and($ownerGate->denies('restore', $rule))->toBeTrue()
        ->and($ownerGate->denies('forceDelete', $rule))->toBeTrue();
});

test('automation rule run policy keeps run history read only', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $rule = AutomationRule::factory()->for($owner)->create();
    $run = AutomationRuleRun::factory()->forRule($rule)->create();

    $ownerGate = Gate::forUser($owner);
    $intruderGate = Gate::forUser($intruder);

    expect($ownerGate->allows('view', $run))->toBeTrue();

    $response = $intruderGate->inspect('view', $run);

    expect($response->denied())->toBeTrue()
        ->and($response->status())->toBe(404)
        ->and($ownerGate->allows('viewAny', AutomationRuleRun::class))->toBeTrue()
        ->and($ownerGate->denies('create', AutomationRuleRun::class))->toBeTrue()
        ->and($ownerGate->denies('update', $run))->toBeTrue()
        ->and($ownerGate->denies('delete', $run))->toBeTrue()
        ->and($ownerGate->denies('restore', $run))->toBeTrue()
        ->and($ownerGate->denies('forceDelete', $run))->toBeTrue();
});

test('goal policy covers owner lifecycle abilities', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $goal = Goal::factory()->for($owner)->create();

    $ownerGate = Gate::forUser($owner);
    $intruderGate = Gate::forUser($intruder);

    foreach (['view', 'update', 'delete'] as $ability) {
        expect($ownerGate->allows($ability, $goal))->toBeTrue();

        $response = $intruderGate->inspect($ability, $goal);

        expect($response->denied())->toBeTrue()
            ->and($response->status())->toBe(404);
    }

    expect($ownerGate->allows('viewAny', Goal::class))->toBeTrue()
        ->and($ownerGate->allows('create', Goal::class))->toBeTrue()
        ->and($ownerGate->denies('restore', $goal))->toBeTrue()
        ->and($ownerGate->denies('forceDelete', $goal))->toBeTrue();
});

test('goal milestone policy covers owner check in abilities', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $goal = Goal::factory()->for($owner)->create();
    $milestone = GoalMilestone::factory()->forGoal($goal)->create();

    $ownerGate = Gate::forUser($owner);
    $intruderGate = Gate::forUser($intruder);

    foreach (['view', 'update', 'delete'] as $ability) {
        expect($ownerGate->allows($ability, $milestone))->toBeTrue();

        $response = $intruderGate->inspect($ability, $milestone);

        expect($response->denied())->toBeTrue()
            ->and($response->status())->toBe(404);
    }

    expect($ownerGate->allows('viewAny', GoalMilestone::class))->toBeTrue()
        ->and($ownerGate->allows('create', GoalMilestone::class))->toBeTrue()
        ->and($ownerGate->denies('restore', $milestone))->toBeTrue()
        ->and($ownerGate->denies('forceDelete', $milestone))->toBeTrue();
});

test('habit policy covers owner lifecycle abilities', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $habit = Habit::factory()->for($owner)->create();

    $ownerGate = Gate::forUser($owner);
    $intruderGate = Gate::forUser($intruder);

    foreach (['view', 'update', 'delete'] as $ability) {
        expect($ownerGate->allows($ability, $habit))->toBeTrue();

        $response = $intruderGate->inspect($ability, $habit);

        expect($response->denied())->toBeTrue()
            ->and($response->status())->toBe(404);
    }

    expect($ownerGate->allows('viewAny', Habit::class))->toBeTrue()
        ->and($ownerGate->allows('create', Habit::class))->toBeTrue()
        ->and($ownerGate->denies('restore', $habit))->toBeTrue()
        ->and($ownerGate->denies('forceDelete', $habit))->toBeTrue();
});

test('habit check in policy covers owner check in abilities', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $habit = Habit::factory()->for($owner)->create();
    $checkIn = HabitCheckIn::factory()->forHabit($habit)->create();

    $ownerGate = Gate::forUser($owner);
    $intruderGate = Gate::forUser($intruder);

    foreach (['view', 'update', 'delete'] as $ability) {
        expect($ownerGate->allows($ability, $checkIn))->toBeTrue();

        $response = $intruderGate->inspect($ability, $checkIn);

        expect($response->denied())->toBeTrue()
            ->and($response->status())->toBe(404);
    }

    expect($ownerGate->allows('viewAny', HabitCheckIn::class))->toBeTrue()
        ->and($ownerGate->allows('create', HabitCheckIn::class))->toBeTrue()
        ->and($ownerGate->denies('restore', $checkIn))->toBeTrue()
        ->and($ownerGate->denies('forceDelete', $checkIn))->toBeTrue();
});

test('pomodoro session policy covers owner timer abilities', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $todo = Todo::factory()->for($owner)->focusCandidate()->create();
    $session = PomodoroSession::factory()->forTodo($todo)->running()->create();

    $ownerGate = Gate::forUser($owner);
    $intruderGate = Gate::forUser($intruder);

    foreach (['view', 'update', 'delete'] as $ability) {
        expect($ownerGate->allows($ability, $session))->toBeTrue();

        $response = $intruderGate->inspect($ability, $session);

        expect($response->denied())->toBeTrue()
            ->and($response->status())->toBe(404);
    }

    expect($ownerGate->allows('viewAny', PomodoroSession::class))->toBeTrue()
        ->and($ownerGate->allows('create', PomodoroSession::class))->toBeTrue()
        ->and($ownerGate->denies('restore', $session))->toBeTrue()
        ->and($ownerGate->denies('forceDelete', $session))->toBeTrue();
});

test('time entry policy covers owner timer and manual log abilities', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $todo = Todo::factory()->for($owner)->create();
    $entry = TimeEntry::factory()->forTodo($todo)->manual()->create();

    $ownerGate = Gate::forUser($owner);
    $intruderGate = Gate::forUser($intruder);

    foreach (['view', 'update', 'delete'] as $ability) {
        expect($ownerGate->allows($ability, $entry))->toBeTrue();

        $response = $intruderGate->inspect($ability, $entry);

        expect($response->denied())->toBeTrue()
            ->and($response->status())->toBe(404);
    }

    expect($ownerGate->allows('viewAny', TimeEntry::class))->toBeTrue()
        ->and($ownerGate->allows('create', TimeEntry::class))->toBeTrue()
        ->and($ownerGate->denies('restore', $entry))->toBeTrue()
        ->and($ownerGate->denies('forceDelete', $entry))->toBeTrue();
});

test('todo dependency policy covers owner blocker abilities', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $todo = Todo::factory()->for($owner)->create();
    $blocker = Todo::factory()->for($owner)->create();
    $dependency = TodoDependency::factory()->forTodos($todo, $blocker)->create();

    $ownerGate = Gate::forUser($owner);
    $intruderGate = Gate::forUser($intruder);

    foreach (['view', 'update', 'delete'] as $ability) {
        expect($ownerGate->allows($ability, $dependency))->toBeTrue();

        $response = $intruderGate->inspect($ability, $dependency);

        expect($response->denied())->toBeTrue()
            ->and($response->status())->toBe(404);
    }

    expect($ownerGate->allows('viewAny', TodoDependency::class))->toBeTrue()
        ->and($ownerGate->allows('create', TodoDependency::class))->toBeTrue()
        ->and($ownerGate->denies('restore', $dependency))->toBeTrue()
        ->and($ownerGate->denies('forceDelete', $dependency))->toBeTrue();
});

test('todo recurrence exception policy covers owner exception abilities', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $source = Todo::factory()->for($owner)->create();
    $rule = TodoRecurrenceRule::factory()->forTodo($source)->create();
    $occurrence = Todo::factory()->generatedOccurrence($rule, '2026-06-07')->create();
    $exception = TodoRecurrenceException::factory()->forOccurrence($occurrence)->edited()->create();

    $ownerGate = Gate::forUser($owner);
    $intruderGate = Gate::forUser($intruder);

    foreach (['view', 'update', 'delete'] as $ability) {
        expect($ownerGate->allows($ability, $exception))->toBeTrue();

        $response = $intruderGate->inspect($ability, $exception);

        expect($response->denied())->toBeTrue()
            ->and($response->status())->toBe(404);
    }

    expect($ownerGate->allows('viewAny', TodoRecurrenceException::class))->toBeTrue()
        ->and($ownerGate->allows('create', TodoRecurrenceException::class))->toBeTrue()
        ->and($ownerGate->denies('restore', $exception))->toBeTrue()
        ->and($ownerGate->denies('forceDelete', $exception))->toBeTrue();
});

test('todo template policy covers owner template abilities', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $template = TodoTemplate::factory()->for($owner)->shared()->create();

    $ownerGate = Gate::forUser($owner);
    $intruderGate = Gate::forUser($intruder);

    foreach (['view', 'update', 'delete', 'instantiate'] as $ability) {
        expect($ownerGate->allows($ability, $template))->toBeTrue();

        $response = $intruderGate->inspect($ability, $template);

        expect($response->denied())->toBeTrue()
            ->and($response->status())->toBe(404);
    }

    expect($ownerGate->allows('viewAny', TodoTemplate::class))->toBeTrue()
        ->and($ownerGate->allows('create', TodoTemplate::class))->toBeTrue()
        ->and($ownerGate->denies('restore', $template))->toBeTrue()
        ->and($ownerGate->denies('forceDelete', $template))->toBeTrue();
});

test('todo policy covers lifecycle and bulk abilities', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $todo = Todo::factory()->for($owner)->create();

    $ownerGate = Gate::forUser($owner);
    $intruderGate = Gate::forUser($intruder);

    foreach (['view', 'update', 'complete', 'reopen', 'archive', 'unarchive', 'delete', 'restore'] as $ability) {
        expect($ownerGate->allows($ability, $todo))->toBeTrue();

        $response = $intruderGate->inspect($ability, $todo);

        expect($response->denied())->toBeTrue()
            ->and($response->status())->toBe(404);
    }

    foreach (['viewAny', 'create', 'clearCompleted', 'bulkComplete', 'bulkArchive', 'bulkUnarchive', 'bulkDelete', 'bulkRestoreDeleted', 'bulkMove'] as $ability) {
        expect($ownerGate->allows($ability, Todo::class))->toBeTrue();
    }

    expect($ownerGate->denies('forceDelete', $todo))->toBeTrue();
});

test('project policy covers owner lifecycle abilities', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $project = Project::factory()->for($owner)->create();

    $ownerGate = Gate::forUser($owner);
    $intruderGate = Gate::forUser($intruder);

    foreach (['view', 'update', 'archive', 'restore', 'delete'] as $ability) {
        expect($ownerGate->allows($ability, $project))->toBeTrue();

        $response = $intruderGate->inspect($ability, $project);

        expect($response->denied())->toBeTrue()
            ->and($response->status())->toBe(404);
    }

    expect($ownerGate->allows('viewAny', Project::class))->toBeTrue()
        ->and($ownerGate->allows('create', Project::class))->toBeTrue()
        ->and($ownerGate->denies('forceDelete', $project))->toBeTrue();
});

test('tag policy covers label abilities and disables unsupported destructive abilities', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $tag = Tag::factory()->for($owner)->create();

    $ownerGate = Gate::forUser($owner);
    $intruderGate = Gate::forUser($intruder);

    foreach (['view', 'update', 'delete'] as $ability) {
        expect($ownerGate->allows($ability, $tag))->toBeTrue();

        $response = $intruderGate->inspect($ability, $tag);

        expect($response->denied())->toBeTrue()
            ->and($response->status())->toBe(404);
    }

    expect($ownerGate->allows('viewAny', Tag::class))->toBeTrue()
        ->and($ownerGate->allows('create', Tag::class))->toBeTrue()
        ->and($ownerGate->denies('restore', $tag))->toBeTrue()
        ->and($ownerGate->denies('forceDelete', $tag))->toBeTrue();
});

test('saved todo view policy covers owner preset abilities', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $savedView = SavedTodoView::factory()->for($owner)->create();

    $ownerGate = Gate::forUser($owner);
    $intruderGate = Gate::forUser($intruder);

    foreach (['view', 'update', 'delete'] as $ability) {
        expect($ownerGate->allows($ability, $savedView))->toBeTrue();

        $response = $intruderGate->inspect($ability, $savedView);

        expect($response->denied())->toBeTrue()
            ->and($response->status())->toBe(404);
    }

    expect($ownerGate->allows('viewAny', SavedTodoView::class))->toBeTrue()
        ->and($ownerGate->allows('create', SavedTodoView::class))->toBeTrue()
        ->and($ownerGate->denies('restore', $savedView))->toBeTrue()
        ->and($ownerGate->denies('forceDelete', $savedView))->toBeTrue();
});

test('todo checklist item policy covers owner contained item abilities', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $todo = Todo::factory()->for($owner)->create();
    $item = TodoChecklistItem::factory()->forTodo($todo)->create();

    $ownerGate = Gate::forUser($owner);
    $intruderGate = Gate::forUser($intruder);

    foreach (['view', 'update', 'delete'] as $ability) {
        expect($ownerGate->allows($ability, $item))->toBeTrue();

        $response = $intruderGate->inspect($ability, $item);

        expect($response->denied())->toBeTrue()
            ->and($response->status())->toBe(404);
    }

    expect($ownerGate->allows('viewAny', TodoChecklistItem::class))->toBeTrue()
        ->and($ownerGate->allows('create', TodoChecklistItem::class))->toBeTrue()
        ->and($ownerGate->denies('restore', $item))->toBeTrue()
        ->and($ownerGate->denies('forceDelete', $item))->toBeTrue();
});

test('reminder policy covers owner web processing abilities', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $todo = Todo::factory()->for($owner)->create();
    $reminder = Reminder::factory()->forTodo($todo)->create();
    $ownerGate = Gate::forUser($owner);
    $intruderGate = Gate::forUser($intruder);

    foreach (['view', 'update', 'delete'] as $ability) {
        expect($ownerGate->allows($ability, $reminder))->toBeTrue();

        $response = $intruderGate->inspect($ability, $reminder);

        expect($response->denied())->toBeTrue()
            ->and($response->status())->toBe(404);
    }

    expect($ownerGate->allows('viewAny', Reminder::class))->toBeTrue()
        ->and($ownerGate->allows('create', Reminder::class))->toBeTrue()
        ->and($ownerGate->allows('process', Reminder::class))->toBeTrue()
        ->and($ownerGate->denies('restore', $reminder))->toBeTrue()
        ->and($ownerGate->denies('forceDelete', $reminder))->toBeTrue();
});

test('todo Livewire actions use policy abilities before mutation', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Index.php'));

    expect($source)
        ->toContain("\$this->authorize('create', Todo::class);")
        ->toContain("\$this->authorize('complete', \$todo);")
        ->toContain("\$this->authorize('reopen', \$todo);")
        ->toContain("\$this->authorize('archive', \$todo);")
        ->toContain("\$this->authorize('unarchive', \$todo);")
        ->toContain("\$this->authorize('delete', \$todo);")
        ->toContain("\$this->authorize('restore', \$todo);")
        ->toContain("\$this->authorize('bulkComplete', Todo::class);")
        ->toContain("\$this->authorize('bulkArchive', Todo::class);")
        ->toContain("\$this->authorize('bulkUnarchive', Todo::class);")
        ->toContain("\$this->authorize('bulkDelete', Todo::class);")
        ->toContain("\$this->authorize('bulkRestoreDeleted', Todo::class);")
        ->toContain("\$this->authorize('bulkMove', Todo::class);")
        ->toContain("\$this->authorize('create', Project::class);")
        ->toContain("\$this->authorize('update', \$project);")
        ->toContain("\$this->authorize('archive', \$project);")
        ->toContain("\$this->authorize('restore', \$project);")
        ->toContain("\$this->authorize('delete', \$project);")
        ->toContain("\$this->authorize('create', Tag::class);")
        ->toContain("\$this->authorize('delete', \$tag);")
        ->toContain("\$this->authorize('create', SavedTodoView::class);")
        ->toContain("\$this->authorize('view', \$savedView);")
        ->toContain("\$this->authorize('delete', \$savedView);");
});

test('template Livewire actions use policy abilities before mutation', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Templates.php'));

    expect($source)
        ->toContain("\$this->authorize('viewAny', TodoTemplate::class);")
        ->toContain("\$this->authorize('update', \$template);");
});

test('task detail checklist Livewire actions use policy abilities before mutation', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Show.php'));

    expect($source)
        ->toContain("\$this->authorize('update', \$this->todo);")
        ->toContain("\$this->authorize('update', \$item);")
        ->toContain("\$this->authorize('delete', \$item);");
});

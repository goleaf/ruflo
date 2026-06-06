<?php

use App\Models\Goal;
use App\Models\GoalMilestone;
use App\Models\Project;
use App\Models\Reminder;
use App\Models\SavedTodoView;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\TodoTemplate;
use App\Models\User;
use App\Policies\GoalMilestonePolicy;
use App\Policies\GoalPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\ReminderPolicy;
use App\Policies\SavedTodoViewPolicy;
use App\Policies\TagPolicy;
use App\Policies\TodoChecklistItemPolicy;
use App\Policies\TodoPolicy;
use App\Policies\TodoTemplatePolicy;
use Illuminate\Support\Facades\Gate;

test('tracked private resources resolve explicit policies', function () {
    expect(Gate::getPolicyFor(Todo::class))->toBeInstanceOf(TodoPolicy::class)
        ->and(Gate::getPolicyFor(Project::class))->toBeInstanceOf(ProjectPolicy::class)
        ->and(Gate::getPolicyFor(Goal::class))->toBeInstanceOf(GoalPolicy::class)
        ->and(Gate::getPolicyFor(GoalMilestone::class))->toBeInstanceOf(GoalMilestonePolicy::class)
        ->and(Gate::getPolicyFor(Tag::class))->toBeInstanceOf(TagPolicy::class)
        ->and(Gate::getPolicyFor(TodoChecklistItem::class))->toBeInstanceOf(TodoChecklistItemPolicy::class)
        ->and(Gate::getPolicyFor(TodoTemplate::class))->toBeInstanceOf(TodoTemplatePolicy::class)
        ->and(Gate::getPolicyFor(SavedTodoView::class))->toBeInstanceOf(SavedTodoViewPolicy::class)
        ->and(Gate::getPolicyFor(Reminder::class))->toBeInstanceOf(ReminderPolicy::class);
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

test('reminder placeholder policy denies every ability until ownership exists', function () {
    $user = User::factory()->create();
    $reminder = Reminder::factory()->create();
    $gate = Gate::forUser($user);

    foreach (['viewAny', 'create'] as $ability) {
        expect($gate->denies($ability, Reminder::class))->toBeTrue();
    }

    foreach (['view', 'update', 'delete', 'restore', 'forceDelete'] as $ability) {
        expect($gate->denies($ability, $reminder))->toBeTrue();
    }
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

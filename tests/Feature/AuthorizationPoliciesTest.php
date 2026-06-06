<?php

use App\Models\Project;
use App\Models\Reminder;
use App\Models\SavedTodoView;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use App\Policies\ProjectPolicy;
use App\Policies\ReminderPolicy;
use App\Policies\SavedTodoViewPolicy;
use App\Policies\TagPolicy;
use App\Policies\TodoPolicy;
use Illuminate\Support\Facades\Gate;

test('tracked private resources resolve explicit policies', function () {
    expect(Gate::getPolicyFor(Todo::class))->toBeInstanceOf(TodoPolicy::class)
        ->and(Gate::getPolicyFor(Project::class))->toBeInstanceOf(ProjectPolicy::class)
        ->and(Gate::getPolicyFor(Tag::class))->toBeInstanceOf(TagPolicy::class)
        ->and(Gate::getPolicyFor(SavedTodoView::class))->toBeInstanceOf(SavedTodoViewPolicy::class)
        ->and(Gate::getPolicyFor(Reminder::class))->toBeInstanceOf(ReminderPolicy::class);
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

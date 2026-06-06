<?php

use App\Actions\Todos\CompleteTodo;
use App\Actions\Todos\CreateTodo;
use App\Data\Todos\TodoData;
use App\Enums\Priority;
use App\Events\TodoCreated;
use App\Livewire\Todos\Index;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

test('create todo action creates an active owned task and dispatches the creation event', function () {
    $user = User::factory()->create();

    Event::fake([TodoCreated::class]);

    $todo = app(CreateTodo::class)->handle($user, new TodoData(
        title: '  Write the task creation contract  ',
        priority: Priority::High,
        dueDate: today()->toDateString(),
    ));

    expect($todo->exists)->toBeTrue()
        ->and($todo->user_id)->toBe($user->id)
        ->and($todo->title)->toBe('Write the task creation contract')
        ->and($todo->priority)->toBe(Priority::High)
        ->and($todo->due_date->toDateString())->toBe(today()->toDateString())
        ->and($todo->is_completed)->toBeFalse()
        ->and($todo->archived_at)->toBeNull()
        ->and($todo->deleted_at)->toBeNull();

    Event::assertDispatched(TodoCreated::class, fn (TodoCreated $event): bool => $event->todo->is($todo));
});

test('create todo action re-scopes organization ids when validation is bypassed', function () {
    $user = User::factory()->create();
    $ownedTag = Tag::factory()->for($user)->create();
    $foreignProject = Project::factory()->create();
    $foreignTag = Tag::factory()->create();

    $todo = app(CreateTodo::class)->handle($user, new TodoData(
        title: 'Do not trust posted ids',
        projectId: $foreignProject->id,
        tagIds: [$ownedTag->id, $foreignTag->id],
    ));

    expect($todo->fresh()->project_id)->toBeNull()
        ->and($todo->tags()->pluck('tags.id')->all())->toBe([$ownedTag->id]);
});

test('task lifecycle and ownership fields cannot be mass assigned during creation', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $project = Project::factory()->for($intruder)->create();

    $todo = new Todo;
    $todo->fill([
        'title' => 'Mass assignment attempt',
        'priority' => Priority::Urgent,
        'due_date' => today()->toDateString(),
        'user_id' => $intruder->id,
        'project_id' => $project->id,
        'is_completed' => true,
        'archived_at' => now(),
        'deleted_at' => now(),
    ]);
    $todo->user()->associate($owner);
    $todo->save();

    $todo->refresh();

    expect($todo->user_id)->toBe($owner->id)
        ->and($todo->project_id)->toBeNull()
        ->and($todo->is_completed)->toBeFalse()
        ->and($todo->archived_at)->toBeNull()
        ->and($todo->deleted_at)->toBeNull()
        ->and($todo->priority)->toBe(Priority::Urgent)
        ->and($todo->due_date->toDateString())->toBe(today()->toDateString());
});

test('complete action still updates the explicit lifecycle field', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['is_completed' => false]);

    app(CompleteTodo::class)->handle($todo);

    expect($todo->refresh()->is_completed)->toBeTrue();
});

test('livewire creation rejects long titles and preserves the entered value', function () {
    $user = User::factory()->create();
    $longTitle = str_repeat('x', 121);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('form.title', $longTitle)
        ->call('createTodo')
        ->assertHasErrors(['form.title' => 'max'])
        ->assertSet('form.title', $longTitle);

    expect($user->todos()->exists())->toBeFalse();
});

test('task create form renders errors beside each editable field', function () {
    $source = file_get_contents(resource_path('views/livewire/todos/index.blade.php'));

    expect($source)
        ->not->toContain('@php')
        ->toContain('name="form.title"')
        ->toContain('name="form.priority"')
        ->toContain('name="form.due_date"')
        ->toContain('model="form.due_date"')
        ->toContain('todo-create-due-date')
        ->toContain('name="form.project_id"')
        ->toContain("@error('form.tag_ids.*')");
});

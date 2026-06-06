<?php

use App\Actions\Todos\CreateTodoFromTemplate;
use App\Actions\Todos\CreateTodoTemplate;
use App\Data\Todos\TodoTemplateData;
use App\Enums\Priority;
use App\Enums\TaskTemplateKind;
use App\Livewire\Todos\Templates;
use App\Models\Project;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\TodoTemplate;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('template page is private and renders only the current users templates', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    TodoTemplate::factory()->for($owner)->routine()->create(['name' => 'Owner routine']);
    TodoTemplate::factory()->for($other)->routine()->create(['name' => 'Other routine']);

    $this->get(route('todos.templates'))
        ->assertRedirect(route('login'));

    $this->actingAs($owner)
        ->get(route('todos.templates'))
        ->assertOk()
        ->assertSee(__('todos.pages.templates.title'))
        ->assertSee('Owner routine')
        ->assertDontSee('Other routine');
});

test('users can create edit instantiate and delete their own templates', function () {
    $user = User::factory()->create();
    Project::factory()->for($user)->create(['name' => 'Existing project']);

    Livewire::actingAs($user)
        ->test(Templates::class)
        ->set('name', '  Weekly review  ')
        ->set('kind', TaskTemplateKind::Routine->value)
        ->set('visibility', 'shared')
        ->set('title', '  Run weekly review  ')
        ->set('description', '  Repeatable planning flow  ')
        ->set('priority', Priority::High->value)
        ->set('dueOffsetDays', '2')
        ->set('projectName', 'Existing project')
        ->set('checklistItems.0', ' Review overdue tasks ')
        ->call('addChecklistItem')
        ->set('checklistItems.1', ' Pick the next priority ')
        ->call('createTemplate')
        ->assertHasNoErrors()
        ->assertSee('Weekly review');

    $template = TodoTemplate::query()->where('name', 'Weekly review')->firstOrFail();

    expect($template->isOwnedBy($user))->toBeTrue()
        ->and($template->kind)->toBe(TaskTemplateKind::Routine)
        ->and($template->visibility)->toBe('shared')
        ->and($template->title)->toBe('Run weekly review')
        ->and($template->due_offset_days)->toBe(2)
        ->and($template->checklist_items)->toBe(['Review overdue tasks', 'Pick the next priority']);

    Livewire::actingAs($user)
        ->test(Templates::class)
        ->call('startEditTemplate', $template->id)
        ->assertSet('editName', 'Weekly review')
        ->set('editName', 'Weekly planning')
        ->set('editKind', TaskTemplateKind::Project->value)
        ->set('editProjectName', 'Launch plan')
        ->set('editTitle', 'Open launch plan')
        ->set('editChecklistItems.0', 'Confirm scope')
        ->call('saveTemplate')
        ->assertHasNoErrors()
        ->call('createTodoFromTemplate', $template->id);

    $template->refresh();
    $todo = Todo::query()->where('title', 'Open launch plan')->firstOrFail();

    expect($template->name)->toBe('Weekly planning')
        ->and($template->kind)->toBe(TaskTemplateKind::Project)
        ->and($todo->isOwnedBy($user))->toBeTrue()
        ->and($todo->project?->name)->toBe('Launch plan')
        ->and($todo->priority)->toBe(Priority::High)
        ->and($todo->due_date->toDateString())->toBe(today()->addDays(2)->toDateString())
        ->and($todo->status()->value)->toBe('active')
        ->and($todo->checklistItems()->pluck('title')->all())->toBe(['Confirm scope', 'Pick the next priority']);

    Livewire::actingAs($user)
        ->test(Templates::class)
        ->call('deleteTemplate', $template->id);

    expect(TodoTemplate::query()->find($template->id))->toBeNull()
        ->and($todo->fresh())->not->toBeNull();
});

test('template validation rejects unsafe and incomplete input', function () {
    $user = User::factory()->create();
    TodoTemplate::factory()->for($user)->create(['name' => 'Duplicate']);

    Livewire::actingAs($user)
        ->test(Templates::class)
        ->set('name', 'Duplicate')
        ->set('kind', TaskTemplateKind::Project->value)
        ->set('title', 'Create the project task')
        ->set('projectName', '   ')
        ->set('checklistItems.0', '')
        ->call('createTemplate')
        ->assertHasErrors(['name', 'projectName']);

    Livewire::actingAs($user)
        ->test(Templates::class)
        ->set('name', 'Checklist starter')
        ->set('kind', TaskTemplateKind::Checklist->value)
        ->set('title', 'Create checklist')
        ->set('checklistItems.0', '')
        ->call('createTemplate')
        ->assertHasErrors(['checklistItems']);

    expect(fn () => TodoTemplateData::fromArray([
        'name' => '   ',
        'kind' => TaskTemplateKind::Task->value,
        'title' => 'Blank name',
        'checklist_items' => [],
    ]))->toThrow(ValidationException::class);
});

test('foreign templates and invalid template ids cannot be used', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $template = TodoTemplate::factory()->for($owner)->shared()->routine()->create();

    expect(fn () => Livewire::actingAs($intruder)
        ->test(Templates::class)
        ->call('startEditTemplate', $template->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => Livewire::actingAs($intruder)
        ->test(Templates::class)
        ->call('createTodoFromTemplate', $template->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => Livewire::actingAs($intruder)
        ->test(Templates::class)
        ->call('deleteTemplate', $template->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => Livewire::actingAs($owner)
        ->test(Templates::class)
        ->call('createTodoFromTemplate', 999999))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => app(CreateTodoFromTemplate::class)->handle($intruder, $template))
        ->toThrow(AuthorizationException::class);
});

test('direct template creation action prevents ownership spoofing', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $template = app(CreateTodoTemplate::class)->handle($user, TodoTemplateData::fromArray([
        'name' => 'Direct routine',
        'kind' => TaskTemplateKind::Routine->value,
        'visibility' => 'private',
        'title' => 'Run direct routine',
        'priority' => Priority::Urgent->value,
        'due_offset_days' => 0,
        'project_name' => null,
        'checklist_items' => ['One direct step'],
    ]));

    expect($template->isOwnedBy($user))->toBeTrue()
        ->and($template->user_id)->not->toBe($other->id);

    $todo = app(CreateTodoFromTemplate::class)->handle($user, $template);

    expect($todo->is_completed)->toBeFalse()
        ->and(TodoChecklistItem::query()->whereBelongsTo($todo)->pluck('title')->all())->toBe(['One direct step']);
});

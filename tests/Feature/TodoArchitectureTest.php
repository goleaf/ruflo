<?php

use App\Actions\Todos\ClearCompletedTodos;
use App\Actions\Todos\CompleteTodo;
use App\Actions\Todos\CreateSavedTodoView;
use App\Actions\Todos\CreateTodo;
use App\Actions\Todos\CreateTodoChecklistItem;
use App\Actions\Todos\CreateTodoFromTemplate;
use App\Actions\Todos\CreateTodoTemplate;
use App\Actions\Todos\DeleteSavedTodoView;
use App\Actions\Todos\DeleteTodo;
use App\Actions\Todos\DeleteTodoChecklistItem;
use App\Actions\Todos\DeleteTodoTemplate;
use App\Actions\Todos\MoveTodoChecklistItem;
use App\Actions\Todos\MoveTodoOnBoard;
use App\Actions\Todos\ReopenTodo;
use App\Actions\Todos\RestoreDeletedTodo;
use App\Actions\Todos\TodoLifecycleStateMachine;
use App\Actions\Todos\ToggleTodoChecklistItem;
use App\Actions\Todos\UpdateTodoChecklistItem;
use App\Actions\Todos\UpdateTodoTemplate;
use App\Data\Todos\BulkActionResult;
use App\Data\Todos\SavedTodoViewData;
use App\Data\Todos\TodoData;
use App\Data\Todos\TodoTemplateData;
use App\Enums\TaskTemplateKind;
use App\Enums\TodoTransition;
use App\Events\TodoChecklistChanged;
use App\Livewire\Forms\Todos\TodoForm;
use App\Livewire\Projects\Show as ProjectShow;
use App\Livewire\Todos\Board as TodoBoard;
use App\Livewire\Todos\Calendar as TodoCalendar;
use App\Livewire\Todos\Show as TodoShow;
use App\Livewire\Todos\Templates as TodoTemplates;
use App\Policies\SavedTodoViewPolicy;
use App\Policies\TodoChecklistItemPolicy;
use App\Policies\TodoPolicy;
use App\Policies\TodoTemplatePolicy;
use App\Queries\Todos\SavedTodoViewListQuery;
use App\Queries\Todos\TodoBoardQuery;
use App\Queries\Todos\TodoCalendarQuery;
use App\Queries\Todos\TodoChecklistItemListQuery;
use App\Queries\Todos\TodoListQuery;
use App\Queries\Todos\TodoTemplateListQuery;
use App\Rules\Todos\BoardStatus;
use App\Rules\Todos\CalendarMonth;
use App\Rules\Todos\ChecklistItemTitle;
use App\Rules\Todos\SavedViewName;
use App\Rules\Todos\TemplateChecklistItems;
use App\Rules\Todos\TemplateName;

test('todo foundation classes exist', function () {
    expect(class_exists(TodoPolicy::class))->toBeTrue()
        ->and(class_exists(TodoForm::class))->toBeTrue()
        ->and(class_exists(TodoData::class))->toBeTrue()
        ->and(class_exists(TodoListQuery::class))->toBeTrue()
        ->and(class_exists(CreateTodo::class))->toBeTrue()
        ->and(class_exists(CompleteTodo::class))->toBeTrue()
        ->and(class_exists(ReopenTodo::class))->toBeTrue()
        ->and(class_exists(DeleteTodo::class))->toBeTrue()
        ->and(class_exists(RestoreDeletedTodo::class))->toBeTrue()
        ->and(class_exists(MoveTodoOnBoard::class))->toBeTrue()
        ->and(class_exists(CreateTodoChecklistItem::class))->toBeTrue()
        ->and(class_exists(UpdateTodoChecklistItem::class))->toBeTrue()
        ->and(class_exists(ToggleTodoChecklistItem::class))->toBeTrue()
        ->and(class_exists(MoveTodoChecklistItem::class))->toBeTrue()
        ->and(class_exists(DeleteTodoChecklistItem::class))->toBeTrue()
        ->and(class_exists(CreateTodoTemplate::class))->toBeTrue()
        ->and(class_exists(UpdateTodoTemplate::class))->toBeTrue()
        ->and(class_exists(DeleteTodoTemplate::class))->toBeTrue()
        ->and(class_exists(CreateTodoFromTemplate::class))->toBeTrue()
        ->and(class_exists(TodoLifecycleStateMachine::class))->toBeTrue()
        ->and(class_exists(CreateSavedTodoView::class))->toBeTrue()
        ->and(class_exists(DeleteSavedTodoView::class))->toBeTrue()
        ->and(class_exists(SavedTodoViewData::class))->toBeTrue()
        ->and(class_exists(TodoTemplateData::class))->toBeTrue()
        ->and(class_exists(SavedTodoViewListQuery::class))->toBeTrue()
        ->and(class_exists(TodoBoardQuery::class))->toBeTrue()
        ->and(class_exists(TodoCalendarQuery::class))->toBeTrue()
        ->and(class_exists(TodoChecklistItemListQuery::class))->toBeTrue()
        ->and(class_exists(TodoTemplateListQuery::class))->toBeTrue()
        ->and(class_exists(BoardStatus::class))->toBeTrue()
        ->and(class_exists(CalendarMonth::class))->toBeTrue()
        ->and(class_exists(ChecklistItemTitle::class))->toBeTrue()
        ->and(class_exists(TemplateChecklistItems::class))->toBeTrue()
        ->and(class_exists(TemplateName::class))->toBeTrue()
        ->and(class_exists(SavedViewName::class))->toBeTrue()
        ->and(class_exists(BulkActionResult::class))->toBeTrue()
        ->and(class_exists(SavedTodoViewPolicy::class))->toBeTrue()
        ->and(class_exists(TodoChecklistItemPolicy::class))->toBeTrue()
        ->and(class_exists(TodoTemplatePolicy::class))->toBeTrue()
        ->and(class_exists(TodoBoard::class))->toBeTrue()
        ->and(class_exists(TodoCalendar::class))->toBeTrue()
        ->and(class_exists(TodoShow::class))->toBeTrue()
        ->and(class_exists(TodoTemplates::class))->toBeTrue()
        ->and(class_exists(ProjectShow::class))->toBeTrue()
        ->and(class_exists(TodoChecklistChanged::class))->toBeTrue()
        ->and(enum_exists(TodoTransition::class))->toBeTrue()
        ->and(enum_exists(TaskTemplateKind::class))->toBeTrue()
        ->and(class_exists(ClearCompletedTodos::class))->toBeTrue();
});

test('todo livewire page delegates domain responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Index.php'));

    expect($source)
        ->toContain('TodoForm')
        ->toContain('CreateTodo')
        ->toContain('TodoListQuery')
        ->toContain('SavedTodoViewListQuery')
        ->toContain('$this->authorize')
        ->not->toContain('Todo::query()')
        ->not->toContain('->create([');
});

test('todo board page delegates movement responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Board.php'));

    expect($source)
        ->toContain('TodoBoardQuery')
        ->toContain('MoveTodoOnBoard')
        ->toContain('BoardStatus')
        ->toContain('OwnedActiveProject')
        ->toContain('$this->authorize')
        ->not->toContain('Todo::query()')
        ->not->toContain('->save()');
});

test('todo calendar page delegates date responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Calendar.php'));

    expect($source)
        ->toContain('TodoCalendarQuery')
        ->toContain('CalendarMonth')
        ->toContain('$this->authorize')
        ->not->toContain('Todo::query()')
        ->not->toContain('->save()');
});

test('todo detail page delegates checklist responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Show.php'));

    expect($source)
        ->toContain('TodoChecklistItemListQuery')
        ->toContain('CreateTodoChecklistItem')
        ->toContain('UpdateTodoChecklistItem')
        ->toContain('ToggleTodoChecklistItem')
        ->toContain('MoveTodoChecklistItem')
        ->toContain('DeleteTodoChecklistItem')
        ->toContain('ChecklistItemTitle')
        ->toContain('$this->authorize')
        ->not->toContain('Todo::query()')
        ->not->toContain('TodoChecklistItem::query()')
        ->not->toContain('->save()');
});

test('todo templates page delegates template responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Templates.php'));

    expect($source)
        ->toContain('TodoTemplateListQuery')
        ->toContain('CreateTodoTemplate')
        ->toContain('UpdateTodoTemplate')
        ->toContain('DeleteTodoTemplate')
        ->toContain('CreateTodoFromTemplate')
        ->toContain('TemplateChecklistItems')
        ->toContain('TemplateName')
        ->toContain('$this->authorize')
        ->not->toContain('TodoTemplate::query()')
        ->not->toContain('Todo::query()')
        ->not->toContain('->save()');
});

test('todo blade view uses translation keys and shared ui components', function () {
    $source = file_get_contents(resource_path('views/livewire/todos/index.blade.php'));

    expect($source)
        ->toContain('<x-ui.page-header')
        ->toContain('<x-ui.empty-state')
        ->toContain('todos.pages.index.title')
        ->not->toContain('Mini todos')
        ->not->toContain('No todos yet.')
        ->not->toContain('Todo added.');
});

test('todo documentation exists for future implementation steps', function () {
    expect(file_exists(base_path('docs/todo-foundation.md')))->toBeTrue()
        ->and(file_exists(base_path('docs/changelog.md')))->toBeTrue()
        ->and(file_exists(base_path('docs/authorization.md')))->toBeTrue()
        ->and(file_exists(base_path('docs/task-lifecycle.md')))->toBeTrue()
        ->and(file_exists(base_path('docs/task-organization.md')))->toBeTrue();
});

test('todo model routes ownership through the shared concern and explicit policy', function () {
    $source = file_get_contents(app_path('Models/Todo.php'));

    expect($source)
        ->toContain('use App\Models\Concerns\BelongsToUser;')
        ->toContain('BelongsToUser')
        ->toContain('#[UsePolicy(TodoPolicy::class)]')
        ->not->toContain("'user_id'");
});

test('todo read queries flow through the owner scope', function () {
    $source = file_get_contents(app_path('Queries/Todos/TodoListQuery.php'));

    expect($source)->toContain('->ownedBy(');
});

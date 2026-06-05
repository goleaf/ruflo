<?php

use App\Actions\Todos\ClearCompletedTodos;
use App\Actions\Todos\CreateTodo;
use App\Actions\Todos\DeleteTodo;
use App\Actions\Todos\ToggleTodoCompletion;
use App\Data\Todos\TodoData;
use App\Livewire\Forms\Todos\TodoForm;
use App\Policies\TodoPolicy;
use App\Queries\Todos\TodoListQuery;

test('todo foundation classes exist', function () {
    expect(class_exists(TodoPolicy::class))->toBeTrue()
        ->and(class_exists(TodoForm::class))->toBeTrue()
        ->and(class_exists(TodoData::class))->toBeTrue()
        ->and(class_exists(TodoListQuery::class))->toBeTrue()
        ->and(class_exists(CreateTodo::class))->toBeTrue()
        ->and(class_exists(ToggleTodoCompletion::class))->toBeTrue()
        ->and(class_exists(DeleteTodo::class))->toBeTrue()
        ->and(class_exists(ClearCompletedTodos::class))->toBeTrue();
});

test('todo livewire page delegates domain responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Index.php'));

    expect($source)
        ->toContain('TodoForm')
        ->toContain('CreateTodo')
        ->toContain('TodoListQuery')
        ->toContain('$this->authorize')
        ->not->toContain('Todo::query()')
        ->not->toContain('->create([');
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
        ->and(file_exists(base_path('docs/authorization.md')))->toBeTrue();
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

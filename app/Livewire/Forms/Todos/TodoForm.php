<?php

namespace App\Livewire\Forms\Todos;

use App\Data\Todos\TodoData;
use App\Enums\Priority;
use App\Models\Todo;
use App\Models\User;
use App\Rules\Todos\OwnedActiveProject;
use App\Rules\Todos\OwnedTag;
use Illuminate\Support\Facades\Auth;
use Livewire\Form;

/**
 * Livewire form state for creating and editing a task.
 *
 * Validation here protects the request shape; ownership of the chosen project
 * and tags is re-verified in the action, never trusted from this input.
 */
class TodoForm extends Form
{
    public string $title = '';

    public string $priority = 'normal';

    public ?string $due_date = null;

    public ?string $project_id = null;

    /** @var array<int, int|string> */
    public array $tag_ids = [];

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $user = $this->currentUser();

        return [
            'title' => ['required', 'string', 'max:120'],
            'priority' => ['required', 'string', 'in:'.implode(',', Priority::values())],
            'due_date' => ['nullable', 'date'],
            'project_id' => ['nullable', 'integer', new OwnedActiveProject($user)],
            'tag_ids' => ['array'],
            'tag_ids.*' => ['integer', new OwnedTag($user)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'title' => __('todos.fields.title'),
            'priority' => __('todos.fields.priority'),
            'due_date' => __('todos.fields.due_date'),
            'project_id' => __('todos.fields.project'),
        ];
    }

    public function data(): TodoData
    {
        return TodoData::fromArray($this->validate());
    }

    /**
     * Preload the form from an existing task for editing.
     */
    public function setFromTodo(Todo $todo): void
    {
        $this->title = $todo->title;
        $this->priority = $todo->priority->value;
        $this->due_date = $todo->due_date?->toDateString();
        $this->project_id = $todo->project_id !== null ? (string) $todo->project_id : null;
        $this->tag_ids = $todo->tags->pluck('id')->all();
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

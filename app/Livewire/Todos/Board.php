<?php

namespace App\Livewire\Todos;

use App\Actions\Todos\MoveTodoOnBoard;
use App\Enums\TodoStatus;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Projects\ProjectListQuery;
use App\Queries\Todos\TodoBoardQuery;
use App\Queries\Todos\TodoListQuery;
use App\Rules\Todos\BoardStatus;
use App\Rules\Todos\OwnedActiveProject;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('todos.pages.board.title')]
class Board extends Component
{
    use AuthorizesRequests;

    /** @var array<int, int|string> */
    public array $projectMoves = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Todo::class);
    }

    public function render(): View
    {
        $columns = app(TodoBoardQuery::class)->columnsFor($this->currentUser());
        $this->syncProjectMoveDefaults($columns);

        return view('livewire.todos.board', [
            'columns' => $columns,
        ]);
    }

    public function moveToStatus(int $todoId, string $targetStatus, TodoListQuery $query, MoveTodoOnBoard $moveTodoOnBoard): void
    {
        Validator::make(
            ['targetStatus' => $targetStatus],
            ['targetStatus' => ['required', new BoardStatus]],
            attributes: ['targetStatus' => __('todos.board.target_status')],
        )->validate();

        $user = $this->currentUser();
        $todo = $query->findVisibleFor($user, $todoId);
        $target = TodoStatus::from($targetStatus);
        $projectId = array_key_exists($todo->id, $this->projectMoves)
            ? $this->validatedProjectId($todo->id, $user)
            : $todo->project_id;

        $moveTodoOnBoard->handle($user, $todo, $target, $projectId);
        $this->afterMove($todo->id);

        Flux::toast(variant: 'success', text: __('todos.messages.board_status_moved'));
    }

    public function moveProject(int $todoId, TodoListQuery $query, MoveTodoOnBoard $moveTodoOnBoard): void
    {
        $user = $this->currentUser();
        $projectId = $this->validatedProjectId($todoId, $user);
        $todo = $query->findVisibleFor($user, $todoId);

        $moveTodoOnBoard->handle($user, $todo, $todo->status(), $projectId);
        $this->afterMove($todo->id);

        Flux::toast(variant: 'success', text: __('todos.messages.board_project_moved'));
    }

    public function moveCardByDrag(int $todoId, int $position, string $targetStatus, TodoListQuery $query, MoveTodoOnBoard $moveTodoOnBoard): void
    {
        Validator::make(
            ['position' => $position, 'targetStatus' => $targetStatus],
            ['position' => ['required', 'integer', 'min:0'], 'targetStatus' => ['required', new BoardStatus]],
            attributes: [
                'position' => __('todos.board.position'),
                'targetStatus' => __('todos.board.target_status'),
            ],
        )->validate();

        $user = $this->currentUser();
        $todo = $query->findVisibleFor($user, $todoId);
        $target = TodoStatus::from($targetStatus);

        if ($todo->status() === $target) {
            return;
        }

        $moveTodoOnBoard->handle($user, $todo, $target, $todo->project_id);
        $this->afterMove($todo->id);

        Flux::toast(variant: 'success', text: __('todos.messages.board_status_moved'));
    }

    /**
     * @return array{active: int, completed: int, archived: int, trash: int, overdue: int}
     */
    #[Computed]
    public function summary(): array
    {
        return app(TodoListQuery::class)->summaryFor($this->currentUser());
    }

    /**
     * @return Collection<int, Project>
     */
    #[Computed]
    public function projects(): Collection
    {
        return app(ProjectListQuery::class)->activeFor($this->currentUser());
    }

    /**
     * @return list<TodoStatus>
     */
    public function boardStatuses(): array
    {
        return [
            TodoStatus::Active,
            TodoStatus::Completed,
            TodoStatus::Archived,
        ];
    }

    public function columnColor(TodoStatus $status): string
    {
        return match ($status) {
            TodoStatus::Active => 'blue',
            TodoStatus::Completed => 'green',
            TodoStatus::Archived => 'zinc',
            TodoStatus::Trash => 'red',
        };
    }

    public function statusIcon(TodoStatus $status): string
    {
        return match ($status) {
            TodoStatus::Active => 'arrow-path',
            TodoStatus::Completed => 'check',
            TodoStatus::Archived => 'archive-box',
            TodoStatus::Trash => 'x-mark',
        };
    }

    private function afterMove(int $todoId): void
    {
        unset($this->summary);
        unset($this->projectMoves[$todoId]);
    }

    private function validatedProjectId(int $todoId, User $user): ?int
    {
        $value = $this->projectMoves[$todoId] ?? '';

        if ($value === '' || $value === null) {
            return null;
        }

        $field = 'projectMoves.'.$todoId;

        Validator::make(
            ['projectMoves' => [$todoId => $value]],
            [$field => ['required', 'integer', new OwnedActiveProject($user)]],
            attributes: [$field => __('todos.fields.project')],
        )->validate();

        return (int) $value;
    }

    /**
     * @param  array<string, array{status: TodoStatus, todos: Collection<int, Todo>}>  $columns
     */
    private function syncProjectMoveDefaults(array $columns): void
    {
        foreach ($columns as $column) {
            foreach ($column['todos'] as $todo) {
                $this->projectMoves[$todo->id] ??= $todo->project_id === null ? '' : (string) $todo->project_id;
            }
        }
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

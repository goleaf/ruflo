<?php

namespace App\Livewire\Todos;

use App\Actions\Todos\CaptureInboxTodo;
use App\Actions\Todos\TriageInboxTodo;
use App\Enums\Priority;
use App\Exceptions\InvalidTodoTransition;
use App\Livewire\Forms\Todos\TodoForm;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Projects\ProjectListQuery;
use App\Queries\Tags\TagListQuery;
use App\Queries\Todos\TodoInboxQuery;
use App\Rules\Todos\InboxCaptureTitle;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('todos.pages.inbox.title')]
class Inbox extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public TodoForm $triageForm;

    public string $captureTitle = '';

    #[Locked]
    public ?int $triagingId = null;

    public bool $showTriageModal = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Todo::class);
    }

    public function render(): View
    {
        return view('livewire.todos.inbox');
    }

    public function capture(CaptureInboxTodo $captureInboxTodo): void
    {
        $this->authorize('create', Todo::class);

        $this->validate(
            $this->captureRules(),
            messages: $this->captureMessages(),
            attributes: $this->captureAttributes(),
        );

        $captured = $captureInboxTodo->handle($this->currentUser(), $this->captureTitle);

        $this->captureTitle = '';
        $this->resetPage();
        unset($this->inboxTodos);

        Flux::toast(variant: 'success', text: __('todos.messages.inbox_captured', ['title' => $captured->title]));
    }

    public function startTriage(int $todoId, TodoInboxQuery $query): void
    {
        $todo = $query->findFor($this->currentUser(), $todoId);

        $this->authorize('update', $todo);

        $this->triagingId = $todo->id;
        $this->triageForm->setFromTodo($todo);
        $this->showTriageModal = true;
    }

    public function saveTriage(TodoInboxQuery $query, TriageInboxTodo $triageInboxTodo): void
    {
        $todo = $query->findFor($this->currentUser(), (int) $this->triagingId);

        $this->authorize('update', $todo);

        try {
            $triageInboxTodo->handle($this->currentUser(), $todo, $this->triageForm->data());
        } catch (InvalidTodoTransition) {
            Flux::toast(variant: 'warning', text: __('todos.messages.cannot_edit_archived'));

            return;
        }

        $this->closeTriage();
        $this->resetPage();
        unset($this->inboxTodos);

        Flux::toast(variant: 'success', text: __('todos.messages.inbox_triaged'));
    }

    public function closeTriage(): void
    {
        $this->triageForm->reset();
        $this->triagingId = null;
        $this->showTriageModal = false;
    }

    /**
     * @return LengthAwarePaginator<int, Todo>
     */
    #[Computed]
    public function inboxTodos(): LengthAwarePaginator
    {
        return app(TodoInboxQuery::class)
            ->for($this->currentUser())
            ->paginate(15);
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
     * @return Collection<int, Tag>
     */
    #[Computed]
    public function tags(): Collection
    {
        return app(TagListQuery::class)->allFor($this->currentUser());
    }

    /**
     * @return list<Priority>
     */
    public function priorityOptions(): array
    {
        return Priority::cases();
    }

    /**
     * @return array<string, mixed>
     */
    private function captureRules(): array
    {
        return [
            'captureTitle' => ['required', 'string', 'max:'.InboxCaptureTitle::MaxLength, new InboxCaptureTitle],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function captureMessages(): array
    {
        return [
            'captureTitle.required' => __('todos.validation.inbox_capture_title'),
            'captureTitle.string' => __('todos.validation.inbox_capture_title'),
            'captureTitle.max' => __('todos.validation.inbox_capture_title'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function captureAttributes(): array
    {
        return [
            'captureTitle' => __('todos.inbox.fields.capture_title'),
        ];
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

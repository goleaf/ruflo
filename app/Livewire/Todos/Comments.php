<?php

namespace App\Livewire\Todos;

use App\Actions\Todos\CreateTodoComment;
use App\Actions\Todos\DeleteTodoComment;
use App\Actions\Todos\UpdateTodoComment;
use App\Http\Requests\Todos\StoreTodoCommentRequest;
use App\Http\Requests\Todos\UpdateTodoCommentRequest;
use App\Models\Todo;
use App\Models\TodoComment;
use App\Models\User;
use App\Queries\Todos\TodoCommentListQuery;
use App\Queries\Todos\TodoListQuery;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class Comments extends Component
{
    use AuthorizesRequests;

    private const int PAGE_SIZE = 8;

    private const int MAX_LIMIT = 80;

    #[Locked]
    public int $todoId;

    #[Locked]
    public int $limit = self::PAGE_SIZE;

    public string $body = '';

    #[Locked]
    public ?int $editingCommentId = null;

    public string $editingBody = '';

    public function mount(int $todoId, TodoListQuery $todos): void
    {
        $todo = $todos->findVisibleFor($this->currentUser(), $todoId);

        $this->authorize('view', $todo);

        $this->todoId = $todo->id;
    }

    public function render(): View
    {
        return view('livewire.todos.comments');
    }

    public function create(CreateTodoComment $createComment): void
    {
        $this->authorize('create', [TodoComment::class, $this->todo]);

        $this->validateCommentBody('body');

        $createComment->handle($this->currentUser(), $this->todo, $this->body);

        $this->body = '';
        $this->clearCommentState();

        Flux::toast(variant: 'success', text: __('todos.comments.messages.created'));
    }

    public function startEditing(int $commentId, TodoCommentListQuery $comments): void
    {
        $comment = $comments->findFor($this->currentUser(), $this->todo, $commentId);
        $this->authorize('update', $comment);

        $this->editingCommentId = $comment->id;
        $this->editingBody = $comment->body;
        $this->resetValidation('editingBody');
    }

    public function update(UpdateTodoComment $updateComment, TodoCommentListQuery $comments): void
    {
        if ($this->editingCommentId === null) {
            return;
        }

        $this->validateCommentBody('editingBody');

        $comment = $comments->findFor($this->currentUser(), $this->todo, $this->editingCommentId);
        $this->authorize('update', $comment);

        $updateComment->handle($this->currentUser(), $comment, $this->editingBody);

        $this->cancelEditing();
        $this->clearCommentState();

        Flux::toast(variant: 'success', text: __('todos.comments.messages.updated'));
    }

    public function cancelEditing(): void
    {
        $this->editingCommentId = null;
        $this->editingBody = '';
        $this->resetValidation('editingBody');
    }

    public function delete(int $commentId, DeleteTodoComment $deleteComment, TodoCommentListQuery $comments): void
    {
        $comment = $comments->findFor($this->currentUser(), $this->todo, $commentId);
        $this->authorize('delete', $comment);

        $deleteComment->handle($this->currentUser(), $comment);

        if ($this->editingCommentId === $commentId) {
            $this->cancelEditing();
        }

        $this->clearCommentState();

        Flux::toast(variant: 'success', text: __('todos.comments.messages.deleted'));
    }

    public function loadMore(): void
    {
        $this->limit = min($this->limit + self::PAGE_SIZE, self::MAX_LIMIT);

        $this->clearCommentState();
    }

    #[Computed]
    public function todo(): Todo
    {
        $todo = app(TodoListQuery::class)->findVisibleFor($this->currentUser(), $this->todoId);

        $this->authorize('view', $todo);

        return $todo;
    }

    /**
     * @return Collection<int, TodoComment>
     */
    #[Computed]
    public function comments(): Collection
    {
        return app(TodoCommentListQuery::class)->recentForTodo($this->currentUser(), $this->todo, $this->limit);
    }

    #[Computed]
    public function totalComments(): int
    {
        return app(TodoCommentListQuery::class)->countForTodo($this->currentUser(), $this->todo);
    }

    #[Computed]
    public function hasMore(): bool
    {
        return app(TodoCommentListQuery::class)->hasMoreThanForTodo($this->currentUser(), $this->todo, $this->limit);
    }

    public function canComment(): bool
    {
        return Gate::forUser($this->currentUser())->allows('create', [TodoComment::class, $this->todo]);
    }

    public function canUpdate(TodoComment $comment): bool
    {
        return Gate::forUser($this->currentUser())->allows('update', $comment);
    }

    public function canDelete(TodoComment $comment): bool
    {
        return Gate::forUser($this->currentUser())->allows('delete', $comment);
    }

    public function authorName(TodoComment $comment): string
    {
        return $comment->author?->name ?? __('todos.comments.author.deleted');
    }

    private function validateCommentBody(string $property): void
    {
        $request = $property === 'editingBody'
            ? UpdateTodoCommentRequest::class
            : StoreTodoCommentRequest::class;

        $this->validate(
            [$property => $request::baseRules()['body']],
            attributes: [$property => $request::attributeNames()['body']],
        );
    }

    private function clearCommentState(): void
    {
        unset($this->comments, $this->totalComments, $this->hasMore, $this->todo);
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}

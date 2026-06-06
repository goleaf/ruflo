<?php

namespace App\Livewire\Todos;

use App\Actions\Todos\CreateTodoComment;
use App\Actions\Todos\DeleteTodoComment;
use App\Actions\Todos\UpdateTodoComment;
use App\Http\Requests\Todos\StoreTodoCommentRequest;
use App\Http\Requests\Todos\UpdateTodoCommentRequest;
use App\Models\Todo;
use App\Models\TodoComment;
use App\Models\TodoCommentMention;
use App\Models\User;
use App\Queries\Todos\TodoCommentListQuery;
use App\Queries\Todos\TodoListQuery;
use App\Queries\Todos\TodoMentionCandidateQuery;
use App\Support\Todos\TodoMentionFormatter;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection as SupportCollection;
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

    public string $mentionSearch = '';

    /** @var list<int> */
    public array $selectedMentionIds = [];

    #[Locked]
    public ?int $editingCommentId = null;

    public string $editingBody = '';

    public string $editingMentionSearch = '';

    /** @var list<int> */
    public array $editingSelectedMentionIds = [];

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

        $createComment->handle($this->currentUser(), $this->todo, $this->body, $this->selectedMentionIds);

        $this->body = '';
        $this->mentionSearch = '';
        $this->selectedMentionIds = [];
        $this->clearCommentState();

        Flux::toast(variant: 'success', text: __('todos.comments.messages.created'));
    }

    public function startEditing(int $commentId, TodoCommentListQuery $comments): void
    {
        $comment = $comments->findFor($this->currentUser(), $this->todo, $commentId);
        $this->authorize('update', $comment);

        $this->editingCommentId = $comment->id;
        $this->editingBody = $comment->body;
        $this->editingMentionSearch = '';
        $this->editingSelectedMentionIds = $comment->mentions
            ->pluck('mentioned_user_id')
            ->map(fn (mixed $userId): int => (int) $userId)
            ->values()
            ->all();
        $this->resetValidation('editingBody');
        $this->clearMentionState();
    }

    public function update(UpdateTodoComment $updateComment, TodoCommentListQuery $comments): void
    {
        if ($this->editingCommentId === null) {
            return;
        }

        $this->validateCommentBody('editingBody');

        $comment = $comments->findFor($this->currentUser(), $this->todo, $this->editingCommentId);
        $this->authorize('update', $comment);

        $updateComment->handle($this->currentUser(), $comment, $this->editingBody, $this->editingSelectedMentionIds);

        $this->cancelEditing();
        $this->clearCommentState();

        Flux::toast(variant: 'success', text: __('todos.comments.messages.updated'));
    }

    public function cancelEditing(): void
    {
        $this->editingCommentId = null;
        $this->editingBody = '';
        $this->editingMentionSearch = '';
        $this->editingSelectedMentionIds = [];
        $this->resetValidation('editingBody');
        $this->clearMentionState();
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

    public function addMention(int $userId, TodoMentionCandidateQuery $mentions, TodoMentionFormatter $formatter): void
    {
        if (! $this->canComment()) {
            return;
        }

        $candidate = $mentions->findCandidateForUser($this->currentUser(), $this->todo, $userId);

        if ($candidate === null) {
            return;
        }

        $this->selectedMentionIds = $this->normalizedMentionIds([...$this->selectedMentionIds, $userId]);
        $this->body = $formatter->appendToken($this->body, $candidate['token']);
        $this->mentionSearch = '';
        $this->resetValidation('body');
        $this->clearMentionState();
    }

    public function removeMention(int $userId, TodoMentionCandidateQuery $mentions, TodoMentionFormatter $formatter): void
    {
        $this->selectedMentionIds = $this->normalizedMentionIds(
            array_values(array_filter($this->selectedMentionIds, fn (mixed $selectedUserId): bool => (int) $selectedUserId !== $userId)),
        );

        $candidate = $mentions->findCandidateForUser($this->currentUser(), $this->todo, $userId);

        if ($candidate !== null) {
            $this->body = $formatter->removeToken($this->body, $candidate['token']);
        }

        $this->clearMentionState();
    }

    public function addEditingMention(int $userId, TodoMentionCandidateQuery $mentions, TodoMentionFormatter $formatter): void
    {
        if ($this->editingCommentId === null) {
            return;
        }

        $comment = app(TodoCommentListQuery::class)->findFor($this->currentUser(), $this->todo, $this->editingCommentId);

        if (Gate::forUser($this->currentUser())->denies('update', $comment)) {
            return;
        }

        $candidate = $mentions->findCandidateForUser($this->currentUser(), $this->todo, $userId);

        if ($candidate === null) {
            return;
        }

        $this->editingSelectedMentionIds = $this->normalizedMentionIds([...$this->editingSelectedMentionIds, $userId]);
        $this->editingBody = $formatter->appendToken($this->editingBody, $candidate['token']);
        $this->editingMentionSearch = '';
        $this->resetValidation('editingBody');
        $this->clearMentionState();
    }

    public function removeEditingMention(int $userId, TodoMentionCandidateQuery $mentions, TodoMentionFormatter $formatter): void
    {
        $this->editingSelectedMentionIds = $this->normalizedMentionIds(
            array_values(array_filter($this->editingSelectedMentionIds, fn (mixed $selectedUserId): bool => (int) $selectedUserId !== $userId)),
        );

        $candidate = $mentions->findCandidateForUser($this->currentUser(), $this->todo, $userId);

        if ($candidate !== null) {
            $this->editingBody = $formatter->removeToken($this->editingBody, $candidate['token']);
        }

        $this->clearMentionState();
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

    /**
     * @return SupportCollection<int, array{id: int, name: string, handle: string, token: string, role: string, role_color: string}>
     */
    #[Computed]
    public function mentionCandidates(): SupportCollection
    {
        if (! $this->canComment()) {
            return collect();
        }

        return app(TodoMentionCandidateQuery::class)->candidatesFor($this->currentUser(), $this->todo, $this->mentionSearch);
    }

    /**
     * @return SupportCollection<int, array{id: int, name: string, handle: string, token: string, role: string, role_color: string}>
     */
    #[Computed]
    public function selectedMentions(): SupportCollection
    {
        if (! $this->canComment()) {
            return collect();
        }

        return app(TodoMentionCandidateQuery::class)->selectedCandidatesFor($this->currentUser(), $this->todo, $this->selectedMentionIds);
    }

    /**
     * @return SupportCollection<int, array{id: int, name: string, handle: string, token: string, role: string, role_color: string}>
     */
    #[Computed]
    public function editingMentionCandidates(): SupportCollection
    {
        if ($this->editingCommentId === null) {
            return collect();
        }

        return app(TodoMentionCandidateQuery::class)->candidatesFor($this->currentUser(), $this->todo, $this->editingMentionSearch);
    }

    /**
     * @return SupportCollection<int, array{id: int, name: string, handle: string, token: string, role: string, role_color: string}>
     */
    #[Computed]
    public function editingSelectedMentions(): SupportCollection
    {
        if ($this->editingCommentId === null) {
            return collect();
        }

        return app(TodoMentionCandidateQuery::class)->selectedCandidatesFor($this->currentUser(), $this->todo, $this->editingSelectedMentionIds);
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

    public function mentionName(TodoCommentMention $mention): string
    {
        return $mention->mentionedUser?->name ?? __('todos.comments.mentions.deleted_user');
    }

    public function mentionHandle(TodoCommentMention $mention): string
    {
        return '@'.$mention->handle;
    }

    public function updatedMentionSearch(): void
    {
        unset($this->mentionCandidates);
    }

    public function updatedEditingMentionSearch(): void
    {
        unset($this->editingMentionCandidates);
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
        $this->clearMentionState();

        unset($this->comments, $this->totalComments, $this->hasMore, $this->todo);
    }

    private function clearMentionState(): void
    {
        unset($this->mentionCandidates, $this->selectedMentions, $this->editingMentionCandidates, $this->editingSelectedMentions);
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return list<int>
     */
    private function normalizedMentionIds(array $ids): array
    {
        return collect($ids)
            ->map(fn (int|string $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}

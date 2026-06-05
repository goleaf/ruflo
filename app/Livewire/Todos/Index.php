<?php

namespace App\Livewire\Todos;

use App\Actions\Projects\ArchiveProject;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\DeleteProject;
use App\Actions\Projects\UnarchiveProject;
use App\Actions\Tags\CreateTag;
use App\Actions\Tags\DeleteTag;
use App\Actions\Todos\ArchiveTodo;
use App\Actions\Todos\BulkArchiveTodos;
use App\Actions\Todos\BulkCompleteTodos;
use App\Actions\Todos\BulkDeleteTodos;
use App\Actions\Todos\ClearCompletedTodos;
use App\Actions\Todos\CreateTodo;
use App\Actions\Todos\DeleteTodo;
use App\Actions\Todos\ToggleTodoCompletion;
use App\Actions\Todos\UnarchiveTodo;
use App\Actions\Todos\UpdateTodo;
use App\Data\Projects\ProjectData;
use App\Data\Tags\TagData;
use App\Enums\Priority;
use App\Enums\TodoStatus;
use App\Exceptions\InvalidTodoTransition;
use App\Livewire\Forms\Todos\TodoForm;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Projects\ProjectListQuery;
use App\Queries\Tags\TagListQuery;
use App\Queries\Todos\TodoFilters;
use App\Queries\Todos\TodoListQuery;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The private task workspace: lifecycle, organization, filtering, and bulk
 * actions.
 *
 * This component holds UI state only. It authorizes every action, resolves
 * every target through owner-scoped queries (never a raw client id), and
 * delegates all reads and writes to query and action classes. Filter inputs
 * arriving via the URL are sanitized in {@see buildFilters()} so a tampered
 * query string can never widen scope or inject sort columns.
 */
#[Title('Todos')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public TodoForm $form;

    public TodoForm $editForm;

    // --- Filter / sort state (URL-persisted) ---
    #[Url(as: 'tab')]
    public string $tab = 'active';

    #[Url]
    public string $search = '';

    #[Url]
    public string $project = '';

    #[Url]
    public string $tag = '';

    #[Url]
    public string $priorityFilter = '';

    #[Url]
    public string $due = '';

    #[Url]
    public string $sort = 'created';

    #[Url]
    public string $direction = 'desc';

    // --- Edit modal ---
    public ?int $editingId = null;

    public bool $showEditModal = false;

    // --- Bulk selection ---
    /** @var array<int, int> */
    public array $selected = [];

    // --- Manage projects/tags modal ---
    public bool $showManageModal = false;

    public string $newProjectName = '';

    public string $newTagName = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Todo::class);

        if (! in_array($this->tab, TodoStatus::tabValues(), true)) {
            $this->tab = TodoStatus::Active->value;
        }
    }

    public function render(): View
    {
        return view('livewire.todos.index');
    }

    // Reset pagination and selection when any filter changes.
    public function updated(string $property): void
    {
        if (in_array($property, ['tab', 'search', 'project', 'tag', 'priorityFilter', 'due', 'sort', 'direction'], true)) {
            $this->resetPage();
            $this->selected = [];
            unset($this->todos);
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'project', 'tag', 'priorityFilter', 'due', 'sort', 'direction']);
        $this->resetPage();
        $this->selected = [];
        unset($this->todos);
    }

    // --- Create / edit ---

    public function createTodo(CreateTodo $createTodo): void
    {
        $this->authorize('create', Todo::class);

        $createTodo->handle($this->currentUser(), $this->form->data());

        $this->form->reset();
        $this->refreshLists();

        Flux::toast(variant: 'success', text: __('todos.messages.created'));
    }

    public function startEdit(int $todoId, TodoListQuery $query): void
    {
        $todo = $query->findVisibleFor($this->currentUser(), $todoId);

        $this->authorize('update', $todo);

        if ($todo->isArchived()) {
            Flux::toast(variant: 'warning', text: __('todos.messages.cannot_edit_archived'));

            return;
        }

        $todo->load('tags');
        $this->editingId = $todo->id;
        $this->editForm->setFromTodo($todo);
        $this->showEditModal = true;
    }

    public function saveEdit(TodoListQuery $query, UpdateTodo $update): void
    {
        $todo = $query->findVisibleFor($this->currentUser(), (int) $this->editingId);

        $this->authorize('update', $todo);

        try {
            $update->handle($this->currentUser(), $todo, $this->editForm->data());
        } catch (InvalidTodoTransition) {
            Flux::toast(variant: 'warning', text: __('todos.messages.cannot_edit_archived'));

            return;
        }

        $this->closeEdit();
        $this->refreshLists();

        Flux::toast(variant: 'success', text: __('todos.messages.updated'));
    }

    public function closeEdit(): void
    {
        $this->editForm->reset();
        $this->editingId = null;
        $this->showEditModal = false;
    }

    // --- Lifecycle ---

    public function toggleTodo(int $todoId, TodoListQuery $query, ToggleTodoCompletion $toggle): void
    {
        $todo = $query->findVisibleFor($this->currentUser(), $todoId);
        $this->authorize('complete', $todo);

        try {
            $toggle->handle($todo);
        } catch (InvalidTodoTransition) {
            Flux::toast(variant: 'warning', text: __('todos.messages.cannot_toggle_archived'));
        }

        $this->refreshLists();
    }

    public function archiveTodo(int $todoId, TodoListQuery $query, ArchiveTodo $archive): void
    {
        $todo = $query->findVisibleFor($this->currentUser(), $todoId);
        $this->authorize('archive', $todo);

        $archive->handle($todo);
        $this->refreshLists();

        Flux::toast(variant: 'success', text: __('todos.messages.archived'));
    }

    public function restoreTodo(int $todoId, TodoListQuery $query, UnarchiveTodo $unarchive): void
    {
        $todo = $query->findVisibleFor($this->currentUser(), $todoId);
        $this->authorize('restore', $todo);

        $unarchive->handle($todo);
        $this->refreshLists();

        Flux::toast(variant: 'success', text: __('todos.messages.restored'));
    }

    public function deleteTodo(int $todoId, TodoListQuery $query, DeleteTodo $delete): void
    {
        $todo = $query->findVisibleFor($this->currentUser(), $todoId);
        $this->authorize('delete', $todo);

        $delete->handle($todo);
        $this->refreshLists();

        Flux::toast(variant: 'success', text: __('todos.messages.deleted'));
    }

    public function clearCompleted(ClearCompletedTodos $clearCompleted): void
    {
        $this->authorize('clearCompleted', Todo::class);

        $deleted = $clearCompleted->handle($this->currentUser());
        $this->refreshLists();

        if ($deleted > 0) {
            Flux::toast(variant: 'success', text: __('todos.messages.completed_cleared'));
        }
    }

    // --- Bulk actions (owner-scoped inside the action; foreign ids are dropped) ---

    public function bulkComplete(BulkCompleteTodos $bulk): void
    {
        $this->authorize('create', Todo::class);
        $count = $bulk->handle($this->currentUser(), $this->selectedIds());
        $this->afterBulk($count);
    }

    public function bulkArchive(BulkArchiveTodos $bulk): void
    {
        $this->authorize('create', Todo::class);
        $count = $bulk->handle($this->currentUser(), $this->selectedIds());
        $this->afterBulk($count);
    }

    public function bulkDelete(BulkDeleteTodos $bulk): void
    {
        $this->authorize('create', Todo::class);
        $count = $bulk->handle($this->currentUser(), $this->selectedIds());
        $this->afterBulk($count);
    }

    private function afterBulk(int $count): void
    {
        $this->selected = [];
        $this->refreshLists();

        if ($count > 0) {
            Flux::toast(variant: 'success', text: __('todos.messages.bulk_done', ['count' => $count]));
        }
    }

    // --- Project management ---

    public function createProject(CreateProject $createProject): void
    {
        $this->authorize('create', Project::class);
        $this->validate(['newProjectName' => ['required', 'string', 'max:120']]);

        $createProject->handle($this->currentUser(), ProjectData::fromArray(['name' => $this->newProjectName]));
        $this->newProjectName = '';
        $this->refreshLists();

        Flux::toast(variant: 'success', text: __('todos.messages.project_created'));
    }

    public function archiveProject(int $projectId, ProjectListQuery $query, ArchiveProject $archive): void
    {
        $project = $query->findVisibleFor($this->currentUser(), $projectId);
        $this->authorize('archive', $project);

        $archive->handle($project);
        $this->refreshLists();
    }

    public function restoreProject(int $projectId, ProjectListQuery $query, UnarchiveProject $unarchive): void
    {
        $project = $query->findVisibleFor($this->currentUser(), $projectId);
        $this->authorize('restore', $project);

        $unarchive->handle($project);
        $this->refreshLists();
    }

    public function deleteProject(int $projectId, ProjectListQuery $query, DeleteProject $delete): void
    {
        $project = $query->findVisibleFor($this->currentUser(), $projectId);
        $this->authorize('delete', $project);

        $delete->handle($project);
        $this->refreshLists();

        Flux::toast(variant: 'success', text: __('todos.messages.project_deleted'));
    }

    // --- Tag management ---

    public function createTag(CreateTag $createTag): void
    {
        $this->authorize('create', Tag::class);
        $this->validate(['newTagName' => ['required', 'string', 'max:50']]);

        $createTag->handle($this->currentUser(), TagData::fromArray(['name' => $this->newTagName]));
        $this->newTagName = '';
        $this->refreshLists();

        Flux::toast(variant: 'success', text: __('todos.messages.tag_created'));
    }

    public function deleteTag(int $tagId, TagListQuery $query, DeleteTag $delete): void
    {
        $tag = $query->findVisibleFor($this->currentUser(), $tagId);
        $this->authorize('delete', $tag);

        $delete->handle($tag);
        $this->refreshLists();

        Flux::toast(variant: 'success', text: __('todos.messages.tag_deleted'));
    }

    // --- Computed reads ---

    /**
     * @return LengthAwarePaginator<int, Todo>
     */
    #[Computed]
    public function todos(): LengthAwarePaginator
    {
        return app(TodoListQuery::class)
            ->filtered($this->currentUser(), $this->buildFilters())
            ->paginate(15);
    }

    /**
     * @return array{active: int, completed: int, archived: int, overdue: int}
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
     * @return Collection<int, Project>
     */
    #[Computed]
    public function allProjects(): Collection
    {
        return app(ProjectListQuery::class)->visibleFor($this->currentUser())->get();
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

    // --- Internals ---

    /**
     * Build a sanitized filter object from the (untrusted) URL-bound state.
     */
    private function buildFilters(): TodoFilters
    {
        $search = trim($this->search);

        return new TodoFilters(
            status: TodoStatus::tryFrom($this->tab) ?? TodoStatus::Active,
            search: $search === '' ? null : $search,
            projectId: ($this->project !== '' && $this->project !== 'none' && ctype_digit($this->project)) ? (int) $this->project : null,
            withoutProject: $this->project === 'none',
            tagId: ($this->tag !== '' && ctype_digit($this->tag)) ? (int) $this->tag : null,
            priority: Priority::tryFrom($this->priorityFilter),
            due: in_array($this->due, TodoFilters::dueOptions(), true) ? $this->due : null,
            sort: in_array($this->sort, TodoFilters::sortOptions(), true) ? $this->sort : 'created',
            direction: $this->direction === 'asc' ? 'asc' : 'desc',
        );
    }

    /**
     * @return list<int>
     */
    private function selectedIds(): array
    {
        return array_values(array_map('intval', $this->selected));
    }

    private function refreshLists(): void
    {
        unset($this->todos, $this->summary, $this->projects, $this->allProjects, $this->tags);
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

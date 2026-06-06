<?php

namespace App\Livewire\Todos;

use App\Data\Todos\TodoCleanupFilters;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Todos\TodoCleanupQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('todos.pages.cleanup.title')]
class Cleanup extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $view = TodoCleanupFilters::Stale;

    #[Url]
    public string $search = '';

    #[Url]
    public string $sort = TodoCleanupFilters::RiskSort;

    #[Url]
    public string $direction = 'desc';

    public function mount(): void
    {
        $this->authorize('viewAny', Todo::class);
    }

    public function render(): View
    {
        return view('livewire.todos.cleanup');
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['view', 'search', 'sort', 'direction'], true)) {
            $this->resetPage();
            unset($this->todos);
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['view', 'search', 'sort', 'direction']);
        $this->resetPage();
        unset($this->todos);
    }

    /**
     * @return LengthAwarePaginator<int, Todo>
     */
    #[Computed]
    public function todos(): LengthAwarePaginator
    {
        return app(TodoCleanupQuery::class)
            ->for($this->currentUser(), $this->filters())
            ->paginate(12);
    }

    /**
     * @return array{stale: int, unplanned: int, blocked: int, risky: int}
     */
    #[Computed]
    public function summary(): array
    {
        return app(TodoCleanupQuery::class)->summaryFor($this->currentUser());
    }

    /**
     * @return list<string>
     */
    public function viewOptions(): array
    {
        return TodoCleanupFilters::viewOptions();
    }

    /**
     * @return list<string>
     */
    public function sortOptions(): array
    {
        return TodoCleanupFilters::sortOptions();
    }

    public function emptyStateTitle(): string
    {
        if ($this->hasInvalidScalarFilter()) {
            return __('todos.cleanup.empty.invalid.title');
        }

        return __('todos.cleanup.empty.'.$this->normalizedView().'.title');
    }

    public function emptyStateDescription(): string
    {
        if ($this->hasInvalidScalarFilter()) {
            return __('todos.cleanup.empty.invalid.description');
        }

        if ($this->normalizedSearch() !== '') {
            return __('todos.cleanup.empty.search_description');
        }

        return __('todos.cleanup.empty.'.$this->normalizedView().'.description');
    }

    /**
     * @return list<array{key: string, label: string, color: string, icon: string}>
     */
    public function activeFilterChips(): array
    {
        $chips = [[
            'key' => 'view',
            'label' => (string) __('todos.cleanup.filters.view_chip', ['view' => $this->viewLabel($this->view)]),
            'color' => $this->viewColor($this->view),
            'icon' => $this->viewIcon($this->view),
        ]];

        $search = $this->normalizedSearch();

        if ($search !== '') {
            $chips[] = [
                'key' => 'search',
                'label' => (string) __('todos.filters.search_chip', ['term' => $search]),
                'color' => 'blue',
                'icon' => 'magnifying-glass',
            ];
        }

        if ($this->sort !== TodoCleanupFilters::RiskSort) {
            $chips[] = [
                'key' => 'sort',
                'label' => (string) __('todos.filters.sort_chip', ['sort' => $this->sortLabel($this->sort)]),
                'color' => 'zinc',
                'icon' => 'adjustments-horizontal',
            ];
        }

        if ($this->direction !== 'desc') {
            $chips[] = [
                'key' => 'direction',
                'label' => (string) __('todos.filters.direction_chip', ['direction' => $this->directionLabel($this->direction)]),
                'color' => 'zinc',
                'icon' => 'adjustments-horizontal',
            ];
        }

        return $chips;
    }

    /**
     * @return list<array{key: string, label: string, color: string, icon: string}>
     */
    public function cleanupBadges(Todo $todo): array
    {
        $badges = [];

        if ($todo->updated_at?->lessThanOrEqualTo(now()->subDays(TodoCleanupQuery::StaleAfterDays))) {
            $badges[] = $this->badgeFor(TodoCleanupFilters::Stale);
        }

        if ($todo->project_id === null && $todo->due_date === null && $todo->inbox_captured_at === null && $todo->tags->isEmpty()) {
            $badges[] = $this->badgeFor(TodoCleanupFilters::Unplanned);
        }

        if ($todo->openBlockerCount() > 0) {
            $badges[] = $this->badgeFor(TodoCleanupFilters::Blocked);
        }

        if ($this->isRisky($todo)) {
            $badges[] = $this->badgeFor(TodoCleanupFilters::Risky);
        }

        return $badges;
    }

    public function viewLabel(string $view): string
    {
        return match ($view) {
            TodoCleanupFilters::Stale => (string) __('todos.cleanup.views.stale'),
            TodoCleanupFilters::Unplanned => (string) __('todos.cleanup.views.unplanned'),
            TodoCleanupFilters::Blocked => (string) __('todos.cleanup.views.blocked'),
            TodoCleanupFilters::Risky => (string) __('todos.cleanup.views.risky'),
            default => (string) __('todos.filters.unavailable_filter'),
        };
    }

    public function viewDescription(string $view): string
    {
        return match ($view) {
            TodoCleanupFilters::Stale => (string) __('todos.cleanup.descriptions.stale', ['days' => TodoCleanupQuery::StaleAfterDays]),
            TodoCleanupFilters::Unplanned => (string) __('todos.cleanup.descriptions.unplanned'),
            TodoCleanupFilters::Blocked => (string) __('todos.cleanup.descriptions.blocked'),
            TodoCleanupFilters::Risky => (string) __('todos.cleanup.descriptions.risky'),
            default => (string) __('todos.cleanup.descriptions.invalid'),
        };
    }

    public function viewIcon(string $view): string
    {
        return match ($view) {
            TodoCleanupFilters::Stale => 'clock',
            TodoCleanupFilters::Unplanned => 'question-mark-circle',
            TodoCleanupFilters::Blocked => 'exclamation-triangle',
            TodoCleanupFilters::Risky => 'shield-exclamation',
            default => 'adjustments-horizontal',
        };
    }

    public function viewColor(string $view): string
    {
        return match ($view) {
            TodoCleanupFilters::Stale => 'amber',
            TodoCleanupFilters::Unplanned => 'blue',
            TodoCleanupFilters::Blocked, TodoCleanupFilters::Risky => 'red',
            default => 'zinc',
        };
    }

    public function sortLabel(string $sort): string
    {
        return TodoCleanupFilters::isValidSort($sort)
            ? (string) __('todos.cleanup.sort.'.$sort)
            : (string) __('todos.filters.unavailable_filter');
    }

    public function directionLabel(string $direction): string
    {
        return match ($direction) {
            'asc' => (string) __('todos.sort.asc'),
            'desc' => (string) __('todos.sort.desc'),
            default => (string) __('todos.filters.unavailable_filter'),
        };
    }

    private function filters(): TodoCleanupFilters
    {
        $search = $this->normalizedSearch();

        return new TodoCleanupFilters(
            view: $this->normalizedView(),
            search: $search === '' ? null : $search,
            sort: TodoCleanupFilters::isValidSort($this->sort) ? $this->sort : TodoCleanupFilters::RiskSort,
            direction: $this->direction === 'asc' ? 'asc' : 'desc',
            hasInvalidFilter: $this->hasInvalidScalarFilter(),
        );
    }

    private function normalizedView(): string
    {
        return TodoCleanupFilters::isValidView($this->view) ? $this->view : TodoCleanupFilters::Stale;
    }

    private function normalizedSearch(): string
    {
        return Str::of($this->search)
            ->squish()
            ->limit(120, '')
            ->value();
    }

    private function hasInvalidScalarFilter(): bool
    {
        return $this->view !== '' && ! TodoCleanupFilters::isValidView($this->view);
    }

    /**
     * @return array{key: string, label: string, color: string, icon: string}
     */
    private function badgeFor(string $view): array
    {
        return [
            'key' => $view,
            'label' => $this->viewLabel($view),
            'color' => $this->viewColor($view),
            'icon' => $this->viewIcon($view),
        ];
    }

    private function isRisky(Todo $todo): bool
    {
        if ($todo->priority->value === 'urgent' && ($todo->due_date === null || ! $todo->due_date->isFuture())) {
            return true;
        }

        if ($todo->priority->value === 'high' && $todo->isOverdue()) {
            return true;
        }

        return $todo->openBlockerCount() > 0
            && $todo->due_date !== null
            && ! $todo->due_date->isFuture();
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

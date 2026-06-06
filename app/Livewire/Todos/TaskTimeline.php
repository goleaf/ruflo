<?php

namespace App\Livewire\Todos;

use App\Models\ActivityRecord;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Activity\ActivityFeedQuery;
use App\Queries\Todos\TodoListQuery;
use App\Support\Activity\ActivityFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class TaskTimeline extends Component
{
    use AuthorizesRequests;

    private const int PAGE_SIZE = 5;

    private const int MAX_LIMIT = 50;

    #[Locked]
    public int $todoId;

    #[Locked]
    public int $limit = self::PAGE_SIZE;

    public function mount(int $todoId, TodoListQuery $todos): void
    {
        $todo = $todos->findVisibleFor($this->currentUser(), $todoId);

        $this->authorize('view', $todo);

        $this->todoId = $todo->id;
    }

    public function render(): View
    {
        return view('livewire.todos.task-timeline');
    }

    public function loadMore(): void
    {
        $this->limit = min($this->limit + self::PAGE_SIZE, self::MAX_LIMIT);

        unset($this->activities, $this->hasMore, $this->todo);
    }

    #[Computed]
    public function todo(): Todo
    {
        return app(TodoListQuery::class)->findVisibleFor($this->currentUser(), $this->todoId);
    }

    /**
     * @return Collection<int, ActivityRecord>
     */
    #[Computed]
    public function activities(): Collection
    {
        return app(ActivityFeedQuery::class)->recentForTodo($this->currentUser(), $this->todo, $this->limit);
    }

    #[Computed]
    public function hasMore(): bool
    {
        return app(ActivityFeedQuery::class)->hasMoreThanForTodo($this->currentUser(), $this->todo, $this->limit);
    }

    public function eventLabel(ActivityRecord $activity): string
    {
        return app(ActivityFormatter::class)->eventLabel($activity);
    }

    public function eventDescription(ActivityRecord $activity): string
    {
        return app(ActivityFormatter::class)->eventDescription($activity);
    }

    public function actorName(ActivityRecord $activity): string
    {
        return app(ActivityFormatter::class)->actorName($activity);
    }

    public function eventColor(ActivityRecord $activity): string
    {
        return app(ActivityFormatter::class)->eventColor($activity);
    }

    public function eventIcon(ActivityRecord $activity): string
    {
        return app(ActivityFormatter::class)->eventIcon($activity);
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}

<?php

namespace App\Livewire\Activity;

use App\Models\ActivityRecord;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Activity\ActivityFeedQuery;
use App\Support\Activity\ActivityFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('activity.pages.index.title')]
final class Index extends Component
{
    private const int PAGE_SIZE = 10;

    private const int MAX_LIMIT = 100;

    #[Locked]
    public int $limit = self::PAGE_SIZE;

    public function render(): View
    {
        return view('livewire.activity.index');
    }

    public function loadMore(): void
    {
        $this->limit = min($this->limit + self::PAGE_SIZE, self::MAX_LIMIT);
        unset($this->activities, $this->hasMore, $this->summary, $this->visibleTodoIds);
    }

    /**
     * @return Collection<int, ActivityRecord>
     */
    #[Computed]
    public function activities(): Collection
    {
        return app(ActivityFeedQuery::class)->recentFor($this->currentUser(), $this->limit);
    }

    #[Computed]
    public function hasMore(): bool
    {
        return app(ActivityFeedQuery::class)->hasMoreThan($this->currentUser(), $this->limit);
    }

    /**
     * @return array{total: int, today: int, tasks: int, checklist: int}
     */
    #[Computed]
    public function summary(): array
    {
        return app(ActivityFeedQuery::class)->summaryFor($this->currentUser());
    }

    /**
     * @return list<int>
     */
    #[Computed]
    public function visibleTodoIds(): array
    {
        $todoMorphClass = (new Todo)->getMorphClass();

        $todoIds = $this->activities
            ->filter(fn (ActivityRecord $activity): bool => $activity->subject_type === $todoMorphClass && $activity->subject_id !== null)
            ->pluck('subject_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        return app(ActivityFeedQuery::class)->visibleTodoIdsFor($this->currentUser(), $todoIds);
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

    public function subjectUrl(ActivityRecord $activity): ?string
    {
        if ($activity->subject_type !== (new Todo)->getMorphClass() || $activity->subject_id === null) {
            return null;
        }

        $todoId = (int) $activity->subject_id;

        if (! in_array($todoId, $this->visibleTodoIds, true)) {
            return null;
        }

        return route('todos.show', $todoId);
    }

    public function changeSummary(ActivityRecord $activity): string
    {
        return app(ActivityFormatter::class)->changeSummary($activity);
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

<?php

namespace App\Livewire\Activity;

use App\Models\ActivityRecord;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Activity\ActivityFeedQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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
        return $this->translationFor($activity, 'label');
    }

    public function eventDescription(ActivityRecord $activity): string
    {
        return $this->translationFor($activity, 'description', [
            'subject' => $activity->subject_title ?? __('activity.subjects.deleted'),
            'count' => (int) data_get($activity->metadata, 'count', 0),
            'item' => (string) data_get($activity->metadata, 'item_title', __('activity.subjects.item')),
            'changes' => $this->changeSummary($activity),
        ]);
    }

    public function actorName(ActivityRecord $activity): string
    {
        return $activity->actor?->name ?? __('activity.actor.system');
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
        $changes = data_get($activity->metadata, 'changes', []);

        if (! is_array($changes) || $changes === []) {
            return __('activity.changes.none');
        }

        $labels = collect(array_keys($changes))
            ->map(fn (string $field): string => $this->fieldLabel($field))
            ->take(3)
            ->join(', ');

        return __('activity.changes.summary', ['fields' => $labels]);
    }

    public function eventColor(ActivityRecord $activity): string
    {
        return match ($activity->event) {
            'todo.completed', 'todo.restored' => 'green',
            'todo.deleted' => 'rose',
            'todo.archived', 'todo.unarchived' => 'zinc',
            'todo.checklist_created',
            'todo.checklist_updated',
            'todo.checklist_completed',
            'todo.checklist_reopened',
            'todo.checklist_moved',
            'todo.checklist_deleted' => 'amber',
            default => 'blue',
        };
    }

    public function eventIcon(ActivityRecord $activity): string
    {
        return match ($activity->event) {
            'todo.created' => 'plus-circle',
            'todo.updated' => 'pencil-square',
            'todo.completed' => 'check-circle',
            'todo.reopened', 'todo.restored' => 'arrow-uturn-left',
            'todo.archived' => 'archive-box',
            'todo.unarchived' => 'arrow-path',
            'todo.deleted' => 'trash',
            'todos.completed_cleared' => 'check',
            default => 'bolt',
        };
    }

    /**
     * @param  array<string, mixed>  $replace
     */
    private function translationFor(ActivityRecord $activity, string $key, array $replace = []): string
    {
        $translationKey = "activity.events.{$activity->event}.{$key}";

        if (__($translationKey) === $translationKey) {
            return Str::headline($activity->event);
        }

        return __($translationKey, $replace);
    }

    private function fieldLabel(string $field): string
    {
        return match ($field) {
            'title' => __('activity.fields.title'),
            'priority' => __('activity.fields.priority'),
            'due_date' => __('activity.fields.due_date'),
            'project_id' => __('activity.fields.project'),
            'todo_category_id' => __('activity.fields.category'),
            'goal_id' => __('activity.fields.goal'),
            'goal_milestone_id' => __('activity.fields.milestone'),
            'habit_id' => __('activity.fields.habit'),
            'tag_ids' => __('activity.fields.tags'),
            default => __('activity.fields.details'),
        };
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}

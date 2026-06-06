<?php

namespace App\Listeners;

use App\Events\CompletedTodosCleared;
use App\Events\TodoArchived;
use App\Events\TodoChecklistChanged;
use App\Events\TodoCommentCreated;
use App\Events\TodoCommentDeleted;
use App\Events\TodoCommentUpdated;
use App\Events\TodoCompleted;
use App\Events\TodoCreated;
use App\Events\TodoDeleted;
use App\Events\TodoReopened;
use App\Events\TodoRestoredFromTrash;
use App\Events\TodoUnarchived;
use App\Events\TodoUpdated;
use App\Models\ActivityRecord;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\TodoComment;
use App\Models\User;

final class RecordTodoActivity
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        match (true) {
            $event instanceof TodoCreated => $this->recordTodo($event->todo, 'todo.created'),
            $event instanceof TodoUpdated => $this->recordTodo($event->todo, 'todo.updated', [
                'changes' => $event->changes,
            ]),
            $event instanceof TodoCompleted => $this->recordTodo($event->todo, 'todo.completed'),
            $event instanceof TodoReopened => $this->recordTodo($event->todo, 'todo.reopened'),
            $event instanceof TodoArchived => $this->recordTodo($event->todo, 'todo.archived'),
            $event instanceof TodoUnarchived => $this->recordTodo($event->todo, 'todo.unarchived'),
            $event instanceof TodoDeleted => $this->recordTodo($event->todo, 'todo.deleted', [
                'deleted' => true,
            ]),
            $event instanceof TodoRestoredFromTrash => $this->recordTodo($event->todo, 'todo.restored'),
            $event instanceof TodoChecklistChanged => $this->recordChecklist($event->todo, $event->item, $event->change),
            $event instanceof TodoCommentCreated => $this->recordComment($event->comment, 'todo.comment_created'),
            $event instanceof TodoCommentUpdated => $this->recordComment($event->comment, 'todo.comment_updated'),
            $event instanceof TodoCommentDeleted => $this->recordComment($event->comment, 'todo.comment_deleted'),
            $event instanceof CompletedTodosCleared => $this->recordUser($event->user, 'todos.completed_cleared', [
                'count' => $event->deletedCount,
            ]),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordTodo(Todo $todo, string $event, array $metadata = []): void
    {
        if ($event === 'todo.updated' && ($metadata['changes'] ?? []) === []) {
            return;
        }

        ActivityRecord::query()->create([
            'user_id' => $todo->user_id,
            'actor_id' => $todo->user_id,
            'event' => $event,
            'subject_type' => $todo->getMorphClass(),
            'subject_id' => $todo->getKey(),
            'subject_title' => $this->todoSubjectTitle($todo),
            'metadata' => $this->safeMetadata($metadata),
            'occurred_at' => now(),
        ]);
    }

    private function recordChecklist(Todo $todo, ?TodoChecklistItem $item, string $change): void
    {
        $this->recordTodo($todo, 'todo.checklist_'.$change, [
            'item_title' => $item instanceof TodoChecklistItem ? $this->safeTitle($item->title, __('activity.subjects.item')) : null,
        ]);
    }

    private function recordComment(TodoComment $comment, string $event): void
    {
        $todo = $comment->todo()->first();

        if (! $todo instanceof Todo) {
            return;
        }

        ActivityRecord::query()->create([
            'user_id' => $todo->user_id,
            'actor_id' => $comment->author_id,
            'event' => $event,
            'subject_type' => $todo->getMorphClass(),
            'subject_id' => $todo->getKey(),
            'subject_title' => $this->todoSubjectTitle($todo),
            'metadata' => $this->safeMetadata([
                'comment_id' => $comment->id,
                'comment_excerpt' => $this->safeTitle($comment->body, __('activity.subjects.comment')),
            ]),
            'occurred_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordUser(User $user, string $event, array $metadata): void
    {
        ActivityRecord::query()->create([
            'user_id' => $user->id,
            'actor_id' => $user->id,
            'event' => $event,
            'subject_type' => null,
            'subject_id' => null,
            'subject_title' => null,
            'metadata' => $this->safeMetadata($metadata),
            'occurred_at' => now(),
        ]);
    }

    private function todoSubjectTitle(Todo $todo): string
    {
        $title = $todo->getAttribute('title');

        if (is_string($title) && trim($title) !== '') {
            return $this->safeTitle($title, __('activity.subjects.deleted'));
        }

        $persistedTitle = Todo::withTrashed()
            ->whereKey($todo->getKey())
            ->value('title');

        return $this->safeTitle(
            is_string($persistedTitle) ? $persistedTitle : null,
            __('activity.subjects.deleted'),
        );
    }

    private function safeTitle(?string $title, ?string $fallback = null): string
    {
        $title = is_string($title) ? $title : $fallback;

        if (! is_string($title) || trim($title) === '') {
            $title = __('activity.subjects.deleted');
        }

        return str($title)->squish()->limit(120, '...')->toString();
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function safeMetadata(array $metadata): array
    {
        if (! array_key_exists('changes', $metadata) || ! is_array($metadata['changes'])) {
            return $metadata;
        }

        $metadata['changes'] = collect($metadata['changes'])
            ->only([
                'title',
                'priority',
                'due_date',
                'project_id',
                'todo_category_id',
                'goal_id',
                'goal_milestone_id',
                'habit_id',
                'tag_ids',
            ])
            ->map(fn (mixed $change): mixed => is_array($change) ? [
                'old' => $this->safeValue($change['old'] ?? null),
                'new' => $this->safeValue($change['new'] ?? null),
            ] : null)
            ->filter()
            ->all();

        return $metadata;
    }

    private function safeValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->safeTitle($value);
        }

        if (is_array($value)) {
            return collect($value)
                ->map(fn (mixed $item): mixed => is_scalar($item) ? $item : null)
                ->filter(fn (mixed $item): bool => $item !== null)
                ->values()
                ->all();
        }

        return is_scalar($value) || $value === null ? $value : null;
    }
}

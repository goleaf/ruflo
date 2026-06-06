<?php

namespace App\Support\Activity;

use App\Models\ActivityRecord;
use Illuminate\Support\Str;

final class ActivityFormatter
{
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
}

<?php

namespace App\Models;

use App\Enums\RecurrenceEndType;
use App\Enums\RecurrenceFrequency;
use App\Enums\RecurrenceWeekday;
use App\Models\Concerns\BelongsToUser;
use App\Policies\TodoRecurrenceRulePolicy;
use Database\Factories\TodoRecurrenceRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['frequency', 'interval', 'starts_on', 'weekdays', 'month_day', 'end_type', 'ends_on', 'max_occurrences', 'is_enabled'])]
#[UsePolicy(TodoRecurrenceRulePolicy::class)]
class TodoRecurrenceRule extends Model
{
    /** @use HasFactory<TodoRecurrenceRuleFactory> */
    use BelongsToUser, HasFactory;

    /**
     * @return BelongsTo<Todo, $this>
     */
    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class)->withTrashed();
    }

    public function summary(): string
    {
        return __('todos.recurrence.summaries.full', [
            'cadence' => $this->cadenceSummary(),
            'start' => $this->starts_on->isoFormat('MMM D, YYYY'),
            'end' => $this->endSummary(),
        ]);
    }

    public function cadenceSummary(): string
    {
        return match ($this->frequency) {
            RecurrenceFrequency::Daily => $this->interval === 1
                ? __('todos.recurrence.summaries.daily')
                : __('todos.recurrence.summaries.daily_interval', ['count' => $this->interval]),
            RecurrenceFrequency::Weekly => __('todos.recurrence.summaries.weekly', [
                'count' => $this->interval,
                'days' => $this->weekdaySummary(),
            ]),
            RecurrenceFrequency::Monthly => __('todos.recurrence.summaries.monthly', [
                'count' => $this->interval,
                'day' => $this->month_day,
            ]),
        };
    }

    public function endSummary(): string
    {
        return match ($this->end_type) {
            RecurrenceEndType::Never => __('todos.recurrence.summaries.never_ends'),
            RecurrenceEndType::OnDate => __('todos.recurrence.summaries.ends_on', [
                'date' => $this->ends_on?->isoFormat('MMM D, YYYY') ?? __('todos.recurrence.none'),
            ]),
            RecurrenceEndType::AfterOccurrences => __('todos.recurrence.summaries.ends_after', [
                'count' => $this->max_occurrences,
            ]),
        };
    }

    public function statusLabel(): string
    {
        return $this->is_enabled
            ? __('todos.recurrence.status.enabled')
            : __('todos.recurrence.status.paused');
    }

    public function statusColor(): string
    {
        return $this->is_enabled ? 'green' : 'zinc';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'frequency' => RecurrenceFrequency::class,
            'interval' => 'integer',
            'starts_on' => 'immutable_date',
            'weekdays' => 'array',
            'month_day' => 'integer',
            'end_type' => RecurrenceEndType::class,
            'ends_on' => 'immutable_date',
            'max_occurrences' => 'integer',
            'is_enabled' => 'boolean',
            'last_generated_until' => 'immutable_date',
        ];
    }

    private function weekdaySummary(): string
    {
        $labels = collect($this->weekdays ?? [])
            ->map(fn (string $weekday): ?string => RecurrenceWeekday::tryFrom($weekday)?->shortLabel())
            ->filter()
            ->values()
            ->all();

        return $labels === []
            ? __('todos.recurrence.none')
            : implode(', ', $labels);
    }
}

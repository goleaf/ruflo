<?php

namespace App\Actions\Todos\Processes;

use App\Contracts\Processing\ManualWebProcess;
use App\Enums\RecurrenceEndType;
use App\Enums\RecurrenceFrequency;
use App\Enums\ReminderStatus;
use App\Models\Reminder;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\TodoRecurrenceRule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

final class GenerateRecurringOccurrencesProcess implements ManualWebProcess
{
    public int $createdCount = 0;

    public int $skippedRuleCount = 0;

    public int $failedCount = 0;

    /**
     * @var array<int, array{id: int, title: string, created: int, through: string, message: string}>
     */
    private array $detailsByRule = [];

    public function __construct(
        private readonly CarbonInterface $windowEnd,
    ) {}

    public function key(): string
    {
        return 'todos.generate_recurring_occurrences';
    }

    /**
     * @return Builder<Model>
     */
    public function query(User $user): Builder
    {
        /** @var Builder<Model> $query */
        $query = TodoRecurrenceRule::query()
            ->ownedBy($user)
            ->where('is_enabled', true)
            ->where('starts_on', '<=', $this->windowEnd->toDateString())
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('last_generated_until')
                    ->orWhere('last_generated_until', '<', $this->windowEnd->toDateString());
            })
            ->whereHas('todo', fn (Builder $todo): Builder => $todo
                ->where('todos.user_id', $user->id)
                ->where('todos.is_completed', false)
                ->whereNull('todos.archived_at')
                ->whereNull('todos.deleted_at'))
            ->with([
                'todo' => fn ($todo) => $todo
                    ->where('todos.user_id', $user->id)
                    ->with([
                        'checklistItems',
                        'tags',
                        'reminders' => fn ($reminders) => $reminders->where('status', ReminderStatus::Pending->value),
                    ]),
            ])
            ->orderBy('starts_on')
            ->orderBy('id');

        return $query;
    }

    public function process(User $user, Model $record): bool
    {
        if (! $record instanceof TodoRecurrenceRule) {
            return false;
        }

        try {
            return DB::transaction(function () use ($user, $record): bool {
                $rule = TodoRecurrenceRule::query()
                    ->ownedBy($user)
                    ->whereKey($record->getKey())
                    ->lockForUpdate()
                    ->with([
                        'todo' => fn ($todo) => $todo
                            ->where('todos.user_id', $user->id)
                            ->with([
                                'checklistItems',
                                'tags',
                                'reminders' => fn ($reminders) => $reminders->where('status', ReminderStatus::Pending->value),
                            ]),
                    ])
                    ->first();

                if (! $rule instanceof TodoRecurrenceRule || ! $rule->todo instanceof Todo || ! $rule->todo->isActive()) {
                    $this->skippedRuleCount++;

                    return false;
                }

                $dates = $this->candidateDates($rule);
                $createdForRule = 0;

                foreach ($dates as $date) {
                    if ($this->hasOccurrenceLimitReached($rule)) {
                        break;
                    }

                    if ($this->occurrenceExists($user, $rule, $date)) {
                        continue;
                    }

                    $this->createOccurrence($user, $rule, $date);
                    $createdForRule++;
                }

                $rule->forceFill([
                    'last_generated_until' => $this->windowEnd->toDateString(),
                ])->save();

                $this->createdCount += $createdForRule;

                if ($createdForRule === 0) {
                    $this->skippedRuleCount++;
                }

                $this->detailsByRule[$rule->id] = [
                    'id' => $rule->id,
                    'title' => $rule->todo->title,
                    'created' => $createdForRule,
                    'through' => $this->windowEnd->toDateString(),
                    'message' => trans_choice('todos.recurrence.generation.detail', $createdForRule, [
                        'count' => $createdForRule,
                        'date' => $this->windowEnd->toDateString(),
                    ]),
                ];

                return $createdForRule > 0;
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->failedCount++;

            return false;
        }
    }

    /**
     * @return array{id: int, title: string, created: int, through: string, message: string}
     */
    public function detail(Model $record): array
    {
        if ($record instanceof TodoRecurrenceRule && isset($this->detailsByRule[$record->id])) {
            return $this->detailsByRule[$record->id];
        }

        $title = $record instanceof TodoRecurrenceRule && $record->todo instanceof Todo
            ? $record->todo->title
            : __('todos.recurrence.missing_task');

        return [
            'id' => (int) $record->getKey(),
            'title' => $title,
            'created' => 0,
            'through' => $this->windowEnd->toDateString(),
            'message' => trans_choice('todos.recurrence.generation.detail', 0, [
                'count' => 0,
                'date' => $this->windowEnd->toDateString(),
            ]),
        ];
    }

    /**
     * @return list<CarbonImmutable>
     */
    private function candidateDates(TodoRecurrenceRule $rule): array
    {
        $startsOn = CarbonImmutable::parse($rule->starts_on)->startOfDay();
        $cursor = $this->cursorStartFor($rule, $startsOn);
        $endsOn = $rule->end_type === RecurrenceEndType::OnDate && $rule->ends_on !== null
            ? CarbonImmutable::parse($rule->ends_on)->endOfDay()
            : $this->windowEnd;
        $windowEnd = ($endsOn->lt($this->windowEnd) ? $endsOn : $this->windowEnd)->startOfDay();

        if ($cursor->gt($windowEnd)) {
            return [];
        }

        $dates = [];

        for ($date = $cursor; $date->lte($windowEnd); $date = $date->addDay()) {
            if ($date->lte($startsOn)) {
                continue;
            }

            if ($this->dateMatchesRule($rule, $startsOn, $date)) {
                $dates[] = $date;
            }
        }

        return $dates;
    }

    private function cursorStartFor(TodoRecurrenceRule $rule, CarbonImmutable $startsOn): CarbonImmutable
    {
        $lastGenerated = $rule->last_generated_until === null
            ? $startsOn
            : CarbonImmutable::parse($rule->last_generated_until);

        return array_reduce([
            $startsOn->addDay(),
            $lastGenerated->addDay(),
            CarbonImmutable::parse(today())->startOfDay(),
        ], fn (?CarbonImmutable $latest, CarbonImmutable $candidate): CarbonImmutable => $latest === null || $candidate->gt($latest) ? $candidate : $latest);
    }

    private function dateMatchesRule(TodoRecurrenceRule $rule, CarbonImmutable $startsOn, CarbonImmutable $date): bool
    {
        return match ($rule->frequency) {
            RecurrenceFrequency::Daily => ((int) $startsOn->diffInDays($date)) % $rule->interval === 0,
            RecurrenceFrequency::Weekly => $this->weeklyDateMatches($rule, $startsOn, $date),
            RecurrenceFrequency::Monthly => $this->monthlyDateMatches($rule, $startsOn, $date),
        };
    }

    private function weeklyDateMatches(TodoRecurrenceRule $rule, CarbonImmutable $startsOn, CarbonImmutable $date): bool
    {
        $weekdays = $rule->weekdays ?? [];
        $weekday = strtolower($date->englishDayOfWeek);
        $weeksSinceStart = (int) $startsOn->startOfWeek()->diffInWeeks($date->startOfWeek());

        return in_array($weekday, $weekdays, true)
            && $weeksSinceStart % $rule->interval === 0;
    }

    private function monthlyDateMatches(TodoRecurrenceRule $rule, CarbonImmutable $startsOn, CarbonImmutable $date): bool
    {
        if ($rule->month_day === null) {
            return false;
        }

        $monthsSinceStart = (int) $startsOn->startOfMonth()->diffInMonths($date->startOfMonth());
        $targetDay = min($rule->month_day, $date->daysInMonth);

        return $monthsSinceStart % $rule->interval === 0
            && $date->day === $targetDay;
    }

    private function hasOccurrenceLimitReached(TodoRecurrenceRule $rule): bool
    {
        if ($rule->end_type !== RecurrenceEndType::AfterOccurrences || $rule->max_occurrences === null) {
            return false;
        }

        return $rule->occurrences()->count() >= $rule->max_occurrences;
    }

    private function occurrenceExists(User $user, TodoRecurrenceRule $rule, CarbonInterface $date): bool
    {
        return Todo::query()
            ->withTrashed()
            ->ownedBy($user)
            ->where('recurrence_rule_id', $rule->id)
            ->whereDate('recurrence_occurs_on', $date->toDateString())
            ->exists();
    }

    private function createOccurrence(User $user, TodoRecurrenceRule $rule, CarbonInterface $date): Todo
    {
        $source = $rule->todo;
        $occurrence = $user->todos()->make([
            'title' => __('todos.recurrence.generation.occurrence_title', [
                'task' => $source->title,
                'date' => $date->isoFormat('MMM D, YYYY'),
            ]),
            'priority' => $source->priority,
            'due_date' => $date->toDateString(),
        ]);

        $occurrence->project_id = $source->project_id;
        $occurrence->goal_id = $source->goal_id;
        $occurrence->goal_milestone_id = $source->goal_milestone_id;
        $occurrence->habit_id = $source->habit_id;
        $occurrence->recurrence_rule_id = $rule->id;
        $occurrence->recurrence_source_todo_id = $source->id;
        $occurrence->recurrence_occurs_on = $date->toDateString();
        $occurrence->recurrence_sequence = $rule->occurrences()->count() + 1;
        $occurrence->save();

        $occurrence->tags()->sync($source->tags->pluck('id')->all());
        $this->copyChecklistItems($user, $source, $occurrence);
        $this->copyPendingReminder($user, $source, $occurrence, $date);

        return $occurrence;
    }

    private function copyChecklistItems(User $user, Todo $source, Todo $occurrence): void
    {
        $source->checklistItems
            ->values()
            ->each(function (TodoChecklistItem $item, int $index) use ($user, $occurrence): void {
                $clone = $occurrence->checklistItems()->make([
                    'title' => $item->title,
                ]);

                $clone->forceFill([
                    'user_id' => $user->id,
                    'title' => $item->title,
                    'is_completed' => false,
                    'completed_at' => null,
                    'position' => $index + 1,
                ])->save();
            });
    }

    private function copyPendingReminder(User $user, Todo $source, Todo $occurrence, CarbonInterface $date): void
    {
        $reminder = $source->reminders
            ->first(fn (Reminder $reminder): bool => $reminder->status === ReminderStatus::Pending && $reminder->remind_at !== null);

        if (! $reminder instanceof Reminder || $source->due_date === null) {
            return;
        }

        $sourceDueAt = CarbonImmutable::parse($source->due_date->toDateString())->startOfDay();
        $offsetSeconds = $reminder->remind_at->getTimestamp() - $sourceDueAt->getTimestamp();
        $targetReminderAt = CarbonImmutable::parse($date->toDateString())->startOfDay()->addSeconds($offsetSeconds);

        $generatedReminder = $user->reminders()->make();
        $generatedReminder->forceFill([
            'todo_id' => $occurrence->id,
            'remind_at' => $targetReminderAt,
            'status' => ReminderStatus::Pending,
            'processed_at' => null,
            'skipped_at' => null,
            'skipped_reason' => null,
            'last_error' => null,
        ])->save();
    }
}

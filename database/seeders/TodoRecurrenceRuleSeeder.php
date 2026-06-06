<?php

namespace Database\Seeders;

use App\Actions\Todos\GenerateRecurringOccurrences;
use App\Actions\Todos\MoveRecurringOccurrence;
use App\Actions\Todos\RecordRecurringOccurrenceEdit;
use App\Actions\Todos\SkipRecurringOccurrence;
use App\Enums\RecurrenceEndType;
use App\Enums\RecurrenceExceptionType;
use App\Enums\RecurrenceFrequency;
use App\Enums\RecurrenceWeekday;
use App\Models\Todo;
use App\Models\TodoRecurrenceException;
use App\Models\TodoRecurrenceRule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Validation\ValidationException;

class TodoRecurrenceRuleSeeder extends Seeder
{
    public function __construct(
        private readonly GenerateRecurringOccurrences $generateRecurringOccurrences,
        private readonly SkipRecurringOccurrence $skipRecurringOccurrence,
        private readonly RecordRecurringOccurrenceEdit $recordRecurringOccurrenceEdit,
        private readonly MoveRecurringOccurrence $moveRecurringOccurrence,
    ) {}

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $demoEmails = collect(config('demo.login_panel.users', []))
            ->pluck('email')
            ->filter()
            ->values()
            ->all();

        if ($demoEmails === []) {
            return;
        }

        User::query()
            ->whereIn('email', $demoEmails)
            ->orderBy('id')
            ->get()
            ->each(function (User $user): void {
                $todos = Todo::query()
                    ->ownedBy($user)
                    ->active()
                    ->orderByRaw('due_date is null')
                    ->orderBy('due_date')
                    ->orderBy('id')
                    ->limit(3)
                    ->get();

                if ($todos->isEmpty()) {
                    return;
                }

                $this->upsertRule($user, $todos[0], [
                    'frequency' => RecurrenceFrequency::Daily,
                    'interval' => 1,
                    'weekdays' => [],
                    'month_day' => null,
                    'end_type' => RecurrenceEndType::Never,
                    'ends_on' => null,
                    'max_occurrences' => null,
                    'is_enabled' => true,
                ]);

                if (isset($todos[1])) {
                    $this->upsertRule($user, $todos[1], [
                        'frequency' => RecurrenceFrequency::Weekly,
                        'interval' => 1,
                        'weekdays' => [
                            RecurrenceWeekday::Monday->value,
                            RecurrenceWeekday::Wednesday->value,
                            RecurrenceWeekday::Friday->value,
                        ],
                        'month_day' => null,
                        'end_type' => RecurrenceEndType::OnDate,
                        'ends_on' => today()->addMonths(6)->toDateString(),
                        'max_occurrences' => null,
                        'is_enabled' => true,
                    ]);
                }

                if (isset($todos[2])) {
                    $this->upsertRule($user, $todos[2], [
                        'frequency' => RecurrenceFrequency::Monthly,
                        'interval' => 1,
                        'weekdays' => [],
                        'month_day' => 15,
                        'end_type' => RecurrenceEndType::AfterOccurrences,
                        'ends_on' => null,
                        'max_occurrences' => 12,
                        'is_enabled' => false,
                    ]);
                }

                $this->generateRecurringOccurrences->handle($user, today()->addWeeks(2));
                $this->seedDemoExceptions($user);
            });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertRule(User $user, Todo $todo, array $attributes): void
    {
        $rule = TodoRecurrenceRule::query()
            ->where('user_id', $user->id)
            ->where('todo_id', $todo->id)
            ->first() ?? new TodoRecurrenceRule;

        $rule->forceFill([
            'user_id' => $user->id,
            'todo_id' => $todo->id,
            ...$attributes,
            'starts_on' => max(today()->toDateString(), $todo->due_date?->toDateString() ?? today()->toDateString()),
            'last_generated_until' => $rule->last_generated_until?->toDateString(),
        ])->save();
    }

    private function seedDemoExceptions(User $user): void
    {
        $occurrences = Todo::query()
            ->ownedBy($user)
            ->active()
            ->whereNotNull('recurrence_rule_id')
            ->whereNotNull('recurrence_source_todo_id')
            ->whereNotNull('recurrence_occurs_on')
            ->whereDoesntHave('recurrenceException')
            ->orderBy('recurrence_occurs_on')
            ->orderBy('id')
            ->limit(6)
            ->get();

        if (! $this->hasDemoException($user, RecurrenceExceptionType::Skipped)) {
            $skipOccurrence = $occurrences->shift();

            if ($skipOccurrence instanceof Todo) {
                try {
                    $this->skipRecurringOccurrence->handle($user, $skipOccurrence->id, 'Demo skipped occurrence');
                } catch (ValidationException) {
                }
            }
        }

        if (! $this->hasDemoException($user, RecurrenceExceptionType::Edited)) {
            $editedOccurrence = $occurrences->shift();

            if ($editedOccurrence instanceof Todo) {
                $editedOccurrence->forceFill([
                    'title' => $editedOccurrence->title.' - edited demo',
                ])->save();

                try {
                    $this->recordRecurringOccurrenceEdit->handle($user, $editedOccurrence->id, 'Demo edited occurrence');
                } catch (ValidationException) {
                }
            }
        }

        if (! $this->hasDemoException($user, RecurrenceExceptionType::Moved)) {
            $movedOccurrence = $occurrences->shift();

            if ($movedOccurrence instanceof Todo) {
                try {
                    $this->moveRecurringOccurrence->handle(
                        $user,
                        $movedOccurrence->id,
                        $this->nextAvailableOccurrenceDate($user, $movedOccurrence),
                        'Demo moved occurrence',
                    );
                } catch (ValidationException) {
                }
            }
        }
    }

    private function hasDemoException(User $user, RecurrenceExceptionType $type): bool
    {
        return TodoRecurrenceException::query()
            ->ownedBy($user)
            ->where('type', $type->value)
            ->exists();
    }

    private function nextAvailableOccurrenceDate(User $user, Todo $occurrence): string
    {
        $date = CarbonImmutable::parse($occurrence->recurrence_occurs_on?->toDateString() ?? today()->toDateString())->addDay();

        while (
            Todo::query()
                ->withTrashed()
                ->ownedBy($user)
                ->where('recurrence_rule_id', $occurrence->recurrence_rule_id)
                ->whereDate('due_date', $date->toDateString())
                ->exists()
        ) {
            $date = $date->addDay();
        }

        return $date->toDateString();
    }
}

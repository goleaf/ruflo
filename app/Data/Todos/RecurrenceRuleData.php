<?php

namespace App\Data\Todos;

use App\Enums\RecurrenceEndType;
use App\Enums\RecurrenceFrequency;
use App\Enums\RecurrenceWeekday;
use App\Rules\Todos\DueDate;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

final readonly class RecurrenceRuleData
{
    /**
     * @param  list<string>  $weekdays
     */
    private function __construct(
        public RecurrenceFrequency $frequency,
        public int $interval,
        public string $startsOn,
        public array $weekdays,
        public ?int $monthDay,
        public RecurrenceEndType $endType,
        public ?string $endsOn,
        public ?int $maxOccurrences,
        public bool $isEnabled,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        $errors = [];

        $frequency = RecurrenceFrequency::tryFrom((string) ($payload['frequency'] ?? ''));
        $interval = self::integerValue($payload['interval'] ?? null);
        $startsOn = DueDate::normalize($payload['starts_on'] ?? null);
        $weekdays = self::weekdays($payload['weekdays'] ?? []);
        $monthDay = self::integerValue($payload['month_day'] ?? null);
        $endType = RecurrenceEndType::tryFrom((string) ($payload['end_type'] ?? ''));
        $endsOn = self::optionalDate($payload['ends_on'] ?? null);
        $maxOccurrences = self::integerValue($payload['max_occurrences'] ?? null);
        $isEnabled = filter_var($payload['is_enabled'] ?? true, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true;

        if (! $frequency instanceof RecurrenceFrequency) {
            $errors['recurrenceFrequency'][] = __('todos.validation.recurrence_frequency');
        }

        if ($interval === null || $interval < 1 || $interval > 30) {
            $errors['recurrenceInterval'][] = __('todos.validation.recurrence_interval');
        }

        if ($startsOn === null) {
            $errors['recurrenceStartsOn'][] = __('todos.validation.recurrence_starts_on');
        } elseif (Carbon::parse($startsOn)->lt(today())) {
            $errors['recurrenceStartsOn'][] = __('todos.validation.recurrence_starts_on_future');
        }

        if (! $endType instanceof RecurrenceEndType) {
            $errors['recurrenceEndType'][] = __('todos.validation.recurrence_end_type');
        }

        if ($frequency === RecurrenceFrequency::Weekly && $weekdays === []) {
            $errors['recurrenceWeekdays'][] = __('todos.validation.recurrence_weekdays');
        }

        if ($frequency === RecurrenceFrequency::Monthly && ($monthDay === null || $monthDay < 1 || $monthDay > 31)) {
            $errors['recurrenceMonthDay'][] = __('todos.validation.recurrence_month_day');
        }

        if ($endType === RecurrenceEndType::OnDate) {
            if ($endsOn === null) {
                $errors['recurrenceEndsOn'][] = __('todos.validation.recurrence_ends_on');
            } elseif ($startsOn !== null && Carbon::parse($endsOn)->lt(Carbon::parse($startsOn))) {
                $errors['recurrenceEndsOn'][] = __('todos.validation.recurrence_ends_after_start');
            } elseif ($startsOn !== null && Carbon::parse($endsOn)->gt(Carbon::parse($startsOn)->addYears(2))) {
                $errors['recurrenceEndsOn'][] = __('todos.validation.recurrence_window');
            }
        }

        if ($endType === RecurrenceEndType::AfterOccurrences && ($maxOccurrences === null || $maxOccurrences < 1 || $maxOccurrences > 365)) {
            $errors['recurrenceMaxOccurrences'][] = __('todos.validation.recurrence_max_occurrences');
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return new self(
            frequency: $frequency,
            interval: $interval,
            startsOn: $startsOn,
            weekdays: $frequency === RecurrenceFrequency::Weekly ? $weekdays : [],
            monthDay: $frequency === RecurrenceFrequency::Monthly ? $monthDay : null,
            endType: $endType,
            endsOn: $endType === RecurrenceEndType::OnDate ? $endsOn : null,
            maxOccurrences: $endType === RecurrenceEndType::AfterOccurrences ? $maxOccurrences : null,
            isEnabled: $isEnabled,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'frequency' => $this->frequency,
            'interval' => $this->interval,
            'starts_on' => $this->startsOn,
            'weekdays' => $this->weekdays,
            'month_day' => $this->monthDay,
            'end_type' => $this->endType,
            'ends_on' => $this->endsOn,
            'max_occurrences' => $this->maxOccurrences,
            'is_enabled' => $this->isEnabled,
        ];
    }

    private static function integerValue(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        return null;
    }

    private static function optionalDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return DueDate::normalize($value);
    }

    /**
     * @return list<string>
     */
    private static function weekdays(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return RecurrenceWeekday::sortValues(array_values(array_filter(
            $value,
            fn (mixed $weekday): bool => is_string($weekday) && RecurrenceWeekday::tryFrom($weekday) instanceof RecurrenceWeekday,
        )));
    }
}

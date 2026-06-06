<?php

namespace App\Data\Habits;

use App\Enums\HabitFrequency;
use App\Models\Habit;
use App\Models\HabitCheckIn;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class HabitProgress
{
    public function __construct(
        public int $completedInPeriod,
        public int $targetInPeriod,
        public int $percent,
        public int $currentStreak,
        public int $bestStreak,
        public bool $checkedInToday,
        public string $periodLabelKey,
    ) {}

    public static function forHabit(Habit $habit, ?CarbonImmutable $today = null): self
    {
        $today ??= today()->toImmutable();
        $checkIns = self::uniqueCheckInsByDate($habit->checkIns);
        $period = self::periodBounds($habit->frequency, $today);
        $completedInPeriod = self::countCheckInsBetween($checkIns, $period['start'], $period['end']);
        $target = max(1, $habit->target_count);
        $checkedInToday = $checkIns->has($today->toDateString());

        return new self(
            completedInPeriod: $completedInPeriod,
            targetInPeriod: $target,
            percent: min(100, (int) round(($completedInPeriod / $target) * 100)),
            currentStreak: self::currentStreak($habit->frequency, $target, $checkIns, $today),
            bestStreak: self::bestStreak($habit->frequency, $target, $checkIns),
            checkedInToday: $checkedInToday,
            periodLabelKey: $habit->frequency->periodTranslationKey(),
        );
    }

    /**
     * @param  Collection<int, HabitCheckIn>  $checkIns
     * @return Collection<string, HabitCheckIn>
     */
    private static function uniqueCheckInsByDate(Collection $checkIns): Collection
    {
        return $checkIns
            ->unique(fn (HabitCheckIn $checkIn): string => $checkIn->occurred_on->toDateString())
            ->keyBy(fn (HabitCheckIn $checkIn): string => $checkIn->occurred_on->toDateString());
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    private static function periodBounds(HabitFrequency $frequency, CarbonImmutable $date): array
    {
        return match ($frequency) {
            HabitFrequency::Daily => [
                'start' => $date->startOfDay(),
                'end' => $date->endOfDay(),
            ],
            HabitFrequency::Weekly => [
                'start' => $date->startOfWeek()->startOfDay(),
                'end' => $date->endOfWeek()->endOfDay(),
            ],
        };
    }

    /**
     * @param  Collection<string, HabitCheckIn>  $checkIns
     */
    private static function countCheckInsBetween(Collection $checkIns, CarbonImmutable $start, CarbonImmutable $end): int
    {
        return $checkIns
            ->filter(fn (HabitCheckIn $checkIn): bool => $checkIn->occurred_on->betweenIncluded($start, $end))
            ->count();
    }

    /**
     * @param  Collection<string, HabitCheckIn>  $checkIns
     */
    private static function currentStreak(HabitFrequency $frequency, int $target, Collection $checkIns, CarbonImmutable $today): int
    {
        $cursor = self::periodComplete($frequency, $target, $checkIns, $today)
            ? $today
            : self::previousPeriod($frequency, $today);
        $streak = 0;

        while (self::periodComplete($frequency, $target, $checkIns, $cursor)) {
            $streak++;
            $cursor = self::previousPeriod($frequency, $cursor);
        }

        return $streak;
    }

    /**
     * @param  Collection<string, HabitCheckIn>  $checkIns
     */
    private static function periodComplete(HabitFrequency $frequency, int $target, Collection $checkIns, CarbonImmutable $date): bool
    {
        $period = self::periodBounds($frequency, $date);

        return self::countCheckInsBetween($checkIns, $period['start'], $period['end']) >= $target;
    }

    private static function previousPeriod(HabitFrequency $frequency, CarbonImmutable $date): CarbonImmutable
    {
        return match ($frequency) {
            HabitFrequency::Daily => $date->subDay(),
            HabitFrequency::Weekly => $date->subWeek(),
        };
    }

    /**
     * @param  Collection<string, HabitCheckIn>  $checkIns
     */
    private static function bestStreak(HabitFrequency $frequency, int $target, Collection $checkIns): int
    {
        if ($checkIns->isEmpty()) {
            return 0;
        }

        $periodStarts = $checkIns
            ->map(fn (HabitCheckIn $checkIn): string => self::periodBounds($frequency, $checkIn->occurred_on->toImmutable())['start']->toDateString())
            ->unique()
            ->sort()
            ->values();

        $best = 0;
        $current = 0;
        $previous = null;

        foreach ($periodStarts as $periodStart) {
            $date = CarbonImmutable::parse($periodStart);

            if (! self::periodComplete($frequency, $target, $checkIns, $date)) {
                $current = 0;
                $previous = null;

                continue;
            }

            $isConsecutive = $previous !== null
                && self::nextPeriodStart($frequency, $previous)->isSameDay($date);

            $current = $isConsecutive ? $current + 1 : 1;
            $best = max($best, $current);
            $previous = $date;
        }

        return $best;
    }

    private static function nextPeriodStart(HabitFrequency $frequency, CarbonImmutable $date): CarbonImmutable
    {
        return match ($frequency) {
            HabitFrequency::Daily => $date->addDay(),
            HabitFrequency::Weekly => $date->addWeek(),
        };
    }
}

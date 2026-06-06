<?php

namespace Database\Seeders;

use App\Enums\RecurrenceEndType;
use App\Enums\RecurrenceFrequency;
use App\Enums\RecurrenceWeekday;
use App\Models\Todo;
use App\Models\TodoRecurrenceRule;
use App\Models\User;
use Illuminate\Database\Seeder;

class TodoRecurrenceRuleSeeder extends Seeder
{
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
            'last_generated_until' => null,
        ])->save();
    }
}

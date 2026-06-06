<?php

use App\Actions\Todos\GenerateRecurringOccurrences;
use App\Actions\Todos\MoveRecurringOccurrence;
use App\Actions\Todos\RecordRecurringOccurrenceEdit;
use App\Actions\Todos\SkipRecurringOccurrence;
use App\Enums\RecurrenceExceptionType;
use App\Enums\RecurrenceFrequency;
use App\Enums\ReminderStatus;
use App\Livewire\Todos\RecurringRules;
use App\Models\Reminder;
use App\Models\Todo;
use App\Models\TodoRecurrenceException;
use App\Models\TodoRecurrenceRule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\TodoRecurrenceRuleSeeder;
use Database\Seeders\TodoSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('skipped recurrence exception prevents deleted generated occurrence from being recreated', function () {
    $this->travelTo('2026-06-06 09:00:00');

    $user = User::factory()->create();
    $source = Todo::factory()->for($user)->dueOn('2026-06-06')->active()->create(['title' => 'Water plants']);
    $rule = TodoRecurrenceRule::factory()->forTodo($source)->afterOccurrences(2)->create([
        'frequency' => RecurrenceFrequency::Daily,
        'starts_on' => '2026-06-06',
    ]);

    app(GenerateRecurringOccurrences::class)->handle($user, CarbonImmutable::parse('2026-06-07'));

    $occurrence = $rule->occurrences()->firstOrFail();

    $exception = app(SkipRecurringOccurrence::class)->handle($user, $occurrence->id, 'Vacation day');

    expect($exception->type)->toBe(RecurrenceExceptionType::Skipped)
        ->and($exception->original_occurs_on->toDateString())->toBe('2026-06-07')
        ->and($occurrence->refresh()->trashed())->toBeTrue();

    $rule->forceFill(['last_generated_until' => null])->save();

    $repeat = app(GenerateRecurringOccurrences::class)->handle($user, CarbonImmutable::parse('2026-06-07'));

    expect($repeat->createdCount)->toBe(0)
        ->and(Todo::withTrashed()->where('recurrence_rule_id', $rule->id)->count())->toBe(1)
        ->and(Todo::query()->where('recurrence_rule_id', $rule->id)->count())->toBe(0)
        ->and(TodoRecurrenceException::query()->where('todo_recurrence_rule_id', $rule->id)->count())->toBe(1);
});

test('moved recurrence exception shifts occurrence date and pending reminders without recreating the original date', function () {
    $this->travelTo('2026-06-06 09:00:00');

    $user = User::factory()->create();
    $source = Todo::factory()->for($user)->dueOn('2026-06-06')->active()->create(['title' => 'Daily review']);
    $rule = TodoRecurrenceRule::factory()->forTodo($source)->afterOccurrences(4)->create([
        'frequency' => RecurrenceFrequency::Daily,
        'starts_on' => '2026-06-06',
    ]);

    Reminder::factory()->forTodo($source)->future(CarbonImmutable::parse('2026-06-06 08:00:00'))->create();

    app(GenerateRecurringOccurrences::class)->handle($user, CarbonImmutable::parse('2026-06-09'));

    $occurrence = $rule->occurrences()->whereDate('recurrence_occurs_on', '2026-06-07')->firstOrFail();
    $reminder = $occurrence->reminders()->firstOrFail();

    expect($reminder->remind_at->format('Y-m-d H:i'))->toBe('2026-06-07 08:00');

    $exception = app(MoveRecurringOccurrence::class)->handle($user, $occurrence->id, '2026-06-10', 'Shift after travel');

    $occurrence->refresh();

    expect($exception->type)->toBe(RecurrenceExceptionType::Moved)
        ->and($exception->original_occurs_on->toDateString())->toBe('2026-06-07')
        ->and($exception->adjusted_occurs_on->toDateString())->toBe('2026-06-10')
        ->and($occurrence->due_date->toDateString())->toBe('2026-06-10')
        ->and($occurrence->recurrence_occurs_on->toDateString())->toBe('2026-06-07')
        ->and($reminder->refresh()->status)->toBe(ReminderStatus::Pending)
        ->and($reminder->remind_at->format('Y-m-d H:i'))->toBe('2026-06-10 08:00');

    $rule->forceFill(['last_generated_until' => null])->save();

    app(GenerateRecurringOccurrences::class)->handle($user, CarbonImmutable::parse('2026-06-10'));

    expect(Todo::query()
        ->where('recurrence_rule_id', $rule->id)
        ->whereDate('due_date', '2026-06-10')
        ->count())->toBe(1)
        ->and(Todo::query()
            ->where('recurrence_rule_id', $rule->id)
            ->whereDate('recurrence_occurs_on', '2026-06-10')
            ->exists())->toBeFalse();
});

test('edited recurrence exception is owner scoped and does not mutate the source series', function () {
    $this->travelTo('2026-06-06 09:00:00');

    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $source = Todo::factory()->for($user)->dueOn('2026-06-06')->active()->create(['title' => 'Source title']);
    $rule = TodoRecurrenceRule::factory()->forTodo($source)->afterOccurrences(1)->create([
        'frequency' => RecurrenceFrequency::Daily,
        'starts_on' => '2026-06-06',
    ]);

    app(GenerateRecurringOccurrences::class)->handle($user, CarbonImmutable::parse('2026-06-07'));

    $occurrence = $rule->occurrences()->firstOrFail();
    $occurrence->forceFill(['title' => 'Edited generated task'])->save();

    app(RecordRecurringOccurrenceEdit::class)->handle($user, $occurrence->id, 'Title adjusted');

    expect(fn () => app(RecordRecurringOccurrenceEdit::class)->handle($otherUser, $occurrence->id))
        ->toThrow(ValidationException::class);

    $exception = TodoRecurrenceException::query()->where('todo_id', $occurrence->id)->firstOrFail();

    expect($exception->type)->toBe(RecurrenceExceptionType::Edited)
        ->and($exception->isOwnedBy($user))->toBeTrue()
        ->and($source->refresh()->title)->toBe('Source title')
        ->and($occurrence->refresh()->title)->toBe('Edited generated task');
});

test('skip and move recurrence exception actions reject foreign generated occurrences', function () {
    $this->travelTo('2026-06-06 09:00:00');

    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $source = Todo::factory()->for($owner)->dueOn('2026-06-06')->active()->create(['title' => 'Owner series']);
    $rule = TodoRecurrenceRule::factory()->forTodo($source)->afterOccurrences(2)->create([
        'frequency' => RecurrenceFrequency::Daily,
        'starts_on' => '2026-06-06',
    ]);

    app(GenerateRecurringOccurrences::class)->handle($owner, CarbonImmutable::parse('2026-06-07'));

    $occurrence = $rule->occurrences()->firstOrFail();

    expect(fn () => app(SkipRecurringOccurrence::class)->handle($intruder, $occurrence->id))
        ->toThrow(ValidationException::class)
        ->and(fn () => app(MoveRecurringOccurrence::class)->handle($intruder, $occurrence->id, '2026-06-10'))
        ->toThrow(ValidationException::class);

    expect(TodoRecurrenceException::query()->count())->toBe(0)
        ->and($occurrence->refresh()->trashed())->toBeFalse();
});

test('recurring rules page manages generated occurrence exceptions through Livewire actions', function () {
    $this->travelTo('2026-06-06 09:00:00');

    $user = User::factory()->create();
    $source = Todo::factory()->for($user)->dueOn('2026-06-06')->active()->create(['title' => 'Ops check']);
    $rule = TodoRecurrenceRule::factory()->forTodo($source)->afterOccurrences(4)->create([
        'frequency' => RecurrenceFrequency::Daily,
        'starts_on' => '2026-06-06',
    ]);

    app(GenerateRecurringOccurrences::class)->handle($user, CarbonImmutable::parse('2026-06-09'));

    $occurrences = $rule->occurrences()->orderBy('recurrence_occurs_on')->get();
    [$skipOccurrence, $editOccurrence, $moveOccurrence] = $occurrences->all();

    Livewire::actingAs($user)
        ->test(RecurringRules::class)
        ->assertSee(__('todos.recurrence.exceptions.occurrences_heading'))
        ->call('recordOccurrenceEdit', $editOccurrence->id)
        ->assertHasNoErrors()
        ->call('startMoveOccurrence', $moveOccurrence->id)
        ->assertSet('movingOccurrenceId', $moveOccurrence->id)
        ->set('moveTo', '2026-06-10')
        ->set('exceptionNote', 'Move from page')
        ->call('moveOccurrence')
        ->assertHasNoErrors()
        ->call('skipOccurrence', $skipOccurrence->id)
        ->assertHasNoErrors();

    expect(TodoRecurrenceException::query()->where('todo_recurrence_rule_id', $rule->id)->count())->toBe(3)
        ->and($skipOccurrence->refresh()->trashed())->toBeTrue()
        ->and($moveOccurrence->refresh()->due_date->toDateString())->toBe('2026-06-10');
});

test('recurrence exception factory states sync owners and dates', function () {
    $user = User::factory()->create();
    $source = Todo::factory()->for($user)->dueOn('2026-06-06')->active()->create(['title' => 'Factory series']);
    $rule = TodoRecurrenceRule::factory()->forTodo($source)->create(['starts_on' => '2026-06-06']);
    $occurrence = Todo::factory()->generatedOccurrence($rule, '2026-06-07')->create();

    $movedException = TodoRecurrenceException::factory()
        ->forOccurrence($occurrence)
        ->moved('2026-06-10')
        ->create();

    $skippedException = TodoRecurrenceException::factory()
        ->forRule($rule, '2026-06-08')
        ->skipped('Factory skip')
        ->create();

    $editedException = TodoRecurrenceException::factory()
        ->forRule($rule, '2026-06-09')
        ->edited('Factory edit')
        ->create();

    expect($movedException->isOwnedBy($user))->toBeTrue()
        ->and($movedException->type)->toBe(RecurrenceExceptionType::Moved)
        ->and($movedException->original_occurs_on->toDateString())->toBe('2026-06-07')
        ->and($movedException->adjusted_occurs_on->toDateString())->toBe('2026-06-10')
        ->and($skippedException->isOwnedBy($user))->toBeTrue()
        ->and($skippedException->type)->toBe(RecurrenceExceptionType::Skipped)
        ->and($skippedException->note)->toBe('Factory skip')
        ->and($editedException->type)->toBe(RecurrenceExceptionType::Edited)
        ->and($editedException->typeLabel())->toBe(__('todos.recurrence.exceptions.types.edited'));
});

test('recurrence seeder adds idempotent demo exceptions for demo users', function () {
    $this->travelTo('2026-06-06 09:00:00');

    $this->seed([DemoUserSeeder::class, TodoSeeder::class, TodoRecurrenceRuleSeeder::class]);

    $users = User::query()->whereIn('email', ['test@example.com', 'second@example.com'])->get();

    expect($users)->toHaveCount(2);

    $users->each(function (User $user): void {
        expect(TodoRecurrenceException::query()->ownedBy($user)->count())->toBe(3)
            ->and(TodoRecurrenceException::query()->ownedBy($user)->where('type', RecurrenceExceptionType::Skipped->value)->count())->toBe(1)
            ->and(TodoRecurrenceException::query()->ownedBy($user)->where('type', RecurrenceExceptionType::Moved->value)->count())->toBe(1)
            ->and(TodoRecurrenceException::query()->ownedBy($user)->where('type', RecurrenceExceptionType::Edited->value)->count())->toBe(1);
    });

    $count = TodoRecurrenceException::query()->count();

    $this->seed(TodoRecurrenceRuleSeeder::class);

    expect(TodoRecurrenceException::query()->count())->toBe($count);
});

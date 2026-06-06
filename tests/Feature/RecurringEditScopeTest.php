<?php

use App\Actions\Todos\GenerateRecurringOccurrences;
use App\Actions\Todos\RecordRecurringOccurrenceEdit;
use App\Actions\Todos\UpdateRecurringOccurrenceDetails;
use App\Actions\Todos\UpdateRecurringSeriesDetails;
use App\Data\Todos\RecurringOccurrenceDetailsData;
use App\Enums\Priority;
use App\Enums\RecurrenceExceptionType;
use App\Enums\RecurrenceFrequency;
use App\Livewire\Todos\RecurringRules;
use App\Models\Reminder;
use App\Models\Todo;
use App\Models\TodoRecurrenceException;
use App\Models\TodoRecurrenceRule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('generated occurrence edits update one occurrence and record the exception without changing the source series', function () {
    $this->travelTo('2026-06-06 09:00:00');

    $user = User::factory()->create();
    $source = Todo::factory()->for($user)->dueOn('2026-06-06')->active()->create([
        'title' => 'Review metrics',
        'priority' => Priority::Normal,
    ]);
    $rule = TodoRecurrenceRule::factory()->forTodo($source)->afterOccurrences(3)->create([
        'frequency' => RecurrenceFrequency::Daily,
        'starts_on' => '2026-06-06',
    ]);

    Reminder::factory()->forTodo($source)->future(CarbonImmutable::parse('2026-06-06 08:00:00'))->create();

    app(GenerateRecurringOccurrences::class)->handle($user, CarbonImmutable::parse('2026-06-09'));

    $occurrence = $rule->occurrences()->whereDate('recurrence_occurs_on', '2026-06-07')->firstOrFail();
    $sibling = $rule->occurrences()->whereDate('recurrence_occurs_on', '2026-06-08')->firstOrFail();
    $reminder = $occurrence->reminders()->firstOrFail();

    $exception = app(UpdateRecurringOccurrenceDetails::class)->handle(
        $user,
        $occurrence->id,
        RecurringOccurrenceDetailsData::occurrence('Edited one occurrence', Priority::Urgent->value, '2026-06-10'),
    );

    expect($exception)->not->toBeNull()
        ->and($exception->type)->toBe(RecurrenceExceptionType::Moved)
        ->and($exception->original_occurs_on->toDateString())->toBe('2026-06-07')
        ->and($exception->adjusted_occurs_on->toDateString())->toBe('2026-06-10')
        ->and($occurrence->refresh()->title)->toBe('Edited one occurrence')
        ->and($occurrence->priority)->toBe(Priority::Urgent)
        ->and($occurrence->due_date->toDateString())->toBe('2026-06-10')
        ->and($occurrence->recurrence_occurs_on->toDateString())->toBe('2026-06-07')
        ->and($reminder->refresh()->remind_at->format('Y-m-d H:i'))->toBe('2026-06-10 08:00')
        ->and($source->refresh()->title)->toBe('Review metrics')
        ->and($source->priority)->toBe(Priority::Normal)
        ->and($sibling->refresh()->title)->not->toBe('Edited one occurrence');
});

test('series edits update the source and future unedited generated occurrences while preserving exception rows and dates', function () {
    $this->travelTo('2026-06-06 09:00:00');

    $user = User::factory()->create();
    $source = Todo::factory()->for($user)->dueOn('2026-06-06')->active()->create([
        'title' => 'Clean workspace',
        'priority' => Priority::Low,
    ]);
    $rule = TodoRecurrenceRule::factory()->forTodo($source)->afterOccurrences(4)->create([
        'frequency' => RecurrenceFrequency::Daily,
        'starts_on' => '2026-06-06',
    ]);

    app(GenerateRecurringOccurrences::class)->handle($user, CarbonImmutable::parse('2026-06-10'));

    $first = $rule->occurrences()->whereDate('recurrence_occurs_on', '2026-06-07')->firstOrFail();
    $edited = $rule->occurrences()->whereDate('recurrence_occurs_on', '2026-06-08')->firstOrFail();
    $future = $rule->occurrences()->whereDate('recurrence_occurs_on', '2026-06-09')->firstOrFail();

    $edited->forceFill(['title' => 'Keep this custom title'])->save();
    app(RecordRecurringOccurrenceEdit::class)->handle($user, $edited->id, 'Custom title');

    $updated = app(UpdateRecurringSeriesDetails::class)->handle(
        $user,
        $first->id,
        RecurringOccurrenceDetailsData::series('Clean desk', Priority::High->value),
    );

    expect($updated)->toBe(3)
        ->and($source->refresh()->title)->toBe('Clean desk')
        ->and($source->priority)->toBe(Priority::High)
        ->and($source->due_date->toDateString())->toBe('2026-06-06')
        ->and($first->refresh()->title)->toBe('Clean desk (Jun 7, 2026)')
        ->and($first->priority)->toBe(Priority::High)
        ->and($first->due_date->toDateString())->toBe('2026-06-07')
        ->and($edited->refresh()->title)->toBe('Keep this custom title')
        ->and($edited->priority)->toBe(Priority::Low)
        ->and($future->refresh()->title)->toBe('Clean desk (Jun 9, 2026)')
        ->and($future->priority)->toBe(Priority::High)
        ->and($future->due_date->toDateString())->toBe('2026-06-09')
        ->and(TodoRecurrenceException::query()->where('todo_id', $edited->id)->firstOrFail()->type)->toBe(RecurrenceExceptionType::Edited);
});

test('recurring rules page edits an occurrence or the series through an explicit Livewire scope modal', function () {
    $this->travelTo('2026-06-06 09:00:00');

    $user = User::factory()->create();
    $source = Todo::factory()->for($user)->dueOn('2026-06-06')->active()->create([
        'title' => 'Inventory stock',
        'priority' => Priority::Normal,
    ]);
    $rule = TodoRecurrenceRule::factory()->forTodo($source)->afterOccurrences(3)->create([
        'frequency' => RecurrenceFrequency::Daily,
        'starts_on' => '2026-06-06',
    ]);

    app(GenerateRecurringOccurrences::class)->handle($user, CarbonImmutable::parse('2026-06-09'));

    $occurrence = $rule->occurrences()->whereDate('recurrence_occurs_on', '2026-06-07')->firstOrFail();
    $future = $rule->occurrences()->whereDate('recurrence_occurs_on', '2026-06-08')->firstOrFail();

    Livewire::actingAs($user)
        ->test(RecurringRules::class)
        ->call('startEditOccurrence', $occurrence->id)
        ->assertSet('editingOccurrenceId', $occurrence->id)
        ->assertSet('editScope', 'occurrence')
        ->set('occurrenceEditTitle', 'Inventory stock exception')
        ->set('occurrenceEditPriority', Priority::Urgent->value)
        ->set('occurrenceEditDueDate', '2026-06-07')
        ->call('saveRecurringEdit')
        ->assertHasNoErrors()
        ->call('startEditOccurrence', $future->id)
        ->set('editScope', 'series')
        ->set('seriesEditTitle', 'Inventory audit')
        ->set('seriesEditPriority', Priority::High->value)
        ->call('saveRecurringEdit')
        ->assertHasNoErrors();

    expect($occurrence->refresh()->title)->toBe('Inventory stock exception')
        ->and($occurrence->priority)->toBe(Priority::Urgent)
        ->and($future->refresh()->title)->toBe('Inventory audit (Jun 8, 2026)')
        ->and($source->refresh()->title)->toBe('Inventory audit')
        ->and(TodoRecurrenceException::query()->where('todo_id', $occurrence->id)->firstOrFail()->type)->toBe(RecurrenceExceptionType::Edited);
});

test('recurring edit scope validates occurrence and series input before mutating tasks', function () {
    $this->travelTo('2026-06-06 09:00:00');

    $user = User::factory()->create();
    $source = Todo::factory()->for($user)->dueOn('2026-06-06')->active()->create([
        'title' => 'Review sprint',
        'priority' => Priority::Normal,
    ]);
    $rule = TodoRecurrenceRule::factory()->forTodo($source)->afterOccurrences(2)->create([
        'frequency' => RecurrenceFrequency::Daily,
        'starts_on' => '2026-06-06',
    ]);

    app(GenerateRecurringOccurrences::class)->handle($user, CarbonImmutable::parse('2026-06-08'));

    $occurrence = $rule->occurrences()->whereDate('recurrence_occurs_on', '2026-06-07')->firstOrFail();

    Livewire::actingAs($user)
        ->test(RecurringRules::class)
        ->call('startEditOccurrence', $occurrence->id)
        ->set('occurrenceEditTitle', '   ')
        ->set('occurrenceEditPriority', 'invalid')
        ->set('occurrenceEditDueDate', 'tomorrow')
        ->call('saveRecurringEdit')
        ->assertHasErrors(['occurrenceEditTitle', 'occurrenceEditPriority', 'occurrenceEditDueDate'])
        ->set('editScope', 'series')
        ->set('seriesEditTitle', '')
        ->set('seriesEditPriority', 'invalid')
        ->call('saveRecurringEdit')
        ->assertHasErrors(['seriesEditTitle', 'seriesEditPriority']);

    expect($occurrence->refresh()->title)->toContain('Review sprint')
        ->and($occurrence->priority)->toBe(Priority::Normal)
        ->and($source->refresh()->title)->toBe('Review sprint')
        ->and($source->priority)->toBe(Priority::Normal);
});

test('recurrence edit scope actions reject foreign generated occurrences', function () {
    $this->travelTo('2026-06-06 09:00:00');

    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $source = Todo::factory()->for($owner)->dueOn('2026-06-06')->active()->create(['title' => 'Owner recurrence']);
    $rule = TodoRecurrenceRule::factory()->forTodo($source)->afterOccurrences(1)->create([
        'frequency' => RecurrenceFrequency::Daily,
        'starts_on' => '2026-06-06',
    ]);

    app(GenerateRecurringOccurrences::class)->handle($owner, CarbonImmutable::parse('2026-06-07'));

    $occurrence = $rule->occurrences()->firstOrFail();
    $occurrenceData = RecurringOccurrenceDetailsData::occurrence('Foreign edit', Priority::High->value, '2026-06-07');
    $seriesData = RecurringOccurrenceDetailsData::series('Foreign series edit', Priority::High->value);

    expect(fn () => app(UpdateRecurringOccurrenceDetails::class)->handle($intruder, $occurrence->id, $occurrenceData))
        ->toThrow(ValidationException::class)
        ->and(fn () => app(UpdateRecurringSeriesDetails::class)->handle($intruder, $occurrence->id, $seriesData))
        ->toThrow(ValidationException::class)
        ->and($occurrence->refresh()->title)->not->toBe('Foreign edit')
        ->and($source->refresh()->title)->toBe('Owner recurrence');
});

<?php

use App\Enums\RecurrenceEndType;
use App\Enums\RecurrenceFrequency;
use App\Enums\RecurrenceWeekday;
use App\Livewire\Todos\RecurringRules;
use App\Models\Todo;
use App\Models\TodoRecurrenceRule;
use App\Models\User;
use Livewire\Livewire;

test('recurring task rules page is protected and renders for verified users', function () {
    $this->get(route('todos.recurring'))->assertRedirect(route('login'));

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('todos.recurring'))
        ->assertOk()
        ->assertSee(__('todos.pages.recurring.title'))
        ->assertSee(__('todos.recurrence.create.heading'));
});

test('user can create a weekly recurrence rule for an owned active task', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->active()->create([
        'title' => 'Publish weekly metrics',
    ]);

    Livewire::actingAs($user)
        ->test(RecurringRules::class)
        ->set('todoId', (string) $todo->id)
        ->set('frequency', RecurrenceFrequency::Weekly->value)
        ->set('interval', '1')
        ->set('startsOn', today()->toDateString())
        ->set('weekdays', [RecurrenceWeekday::Monday->value, RecurrenceWeekday::Wednesday->value])
        ->set('endType', RecurrenceEndType::OnDate->value)
        ->set('endsOn', today()->addMonths(2)->toDateString())
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Publish weekly metrics');

    $rule = TodoRecurrenceRule::query()->sole();

    expect($rule->isOwnedBy($user))->toBeTrue()
        ->and($rule->todo->is($todo))->toBeTrue()
        ->and($rule->frequency)->toBe(RecurrenceFrequency::Weekly)
        ->and($rule->weekdays)->toBe([RecurrenceWeekday::Monday->value, RecurrenceWeekday::Wednesday->value])
        ->and($rule->ends_on->toDateString())->toBe(today()->addMonths(2)->toDateString())
        ->and($rule->is_enabled)->toBeTrue();
});

test('recurrence rules reject foreign and inactive task ids without leaking records', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $foreignTodo = Todo::factory()->for($other)->active()->create();
    $archivedTodo = Todo::factory()->for($user)->archived()->create();

    Livewire::actingAs($user)
        ->test(RecurringRules::class)
        ->set('todoId', (string) $foreignTodo->id)
        ->set('startsOn', today()->toDateString())
        ->call('save')
        ->assertHasErrors('todoId');

    Livewire::actingAs($user)
        ->test(RecurringRules::class)
        ->set('todoId', (string) $archivedTodo->id)
        ->set('startsOn', today()->toDateString())
        ->call('save')
        ->assertHasErrors('todoId');

    expect(TodoRecurrenceRule::query()->count())->toBe(0);
});

test('recurrence rule validation catches invalid schedule combinations', function (array $overrides) {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->active()->create();

    Livewire::actingAs($user)
        ->test(RecurringRules::class)
        ->set('todoId', (string) $todo->id)
        ->set('frequency', $overrides['frequency'])
        ->set('interval', $overrides['interval'] ?? '1')
        ->set('startsOn', $overrides['startsOn'] ?? today()->toDateString())
        ->set('weekdays', $overrides['weekdays'] ?? [])
        ->set('monthDay', $overrides['monthDay'] ?? '')
        ->set('endType', $overrides['endType'] ?? RecurrenceEndType::Never->value)
        ->set('endsOn', $overrides['endsOn'] ?? '')
        ->set('maxOccurrences', $overrides['maxOccurrences'] ?? '')
        ->call('save')
        ->assertHasErrors('recurrenceRule');

    expect(TodoRecurrenceRule::query()->count())->toBe(0);
})->with([
    'weekly without weekday' => [[
        'frequency' => RecurrenceFrequency::Weekly->value,
        'weekdays' => [],
    ]],
    'monthly without month day' => [[
        'frequency' => RecurrenceFrequency::Monthly->value,
        'monthDay' => '',
    ]],
    'past start date' => [[
        'frequency' => RecurrenceFrequency::Daily->value,
        'startsOn' => today()->subDay()->toDateString(),
    ]],
    'end date outside bounded window' => [[
        'frequency' => RecurrenceFrequency::Daily->value,
        'endType' => RecurrenceEndType::OnDate->value,
        'endsOn' => today()->addYears(3)->toDateString(),
    ]],
]);

test('user can edit pause enable and delete an owned recurrence rule', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->active()->create();
    $rule = TodoRecurrenceRule::factory()->forTodo($todo)->weekly()->create([
        'starts_on' => today()->toDateString(),
    ]);

    Livewire::actingAs($user)
        ->test(RecurringRules::class)
        ->call('startEditRule', $rule->id)
        ->assertSet('editingRuleId', $rule->id)
        ->set('interval', '2')
        ->set('weekdays', [RecurrenceWeekday::Friday->value])
        ->call('save')
        ->assertHasNoErrors()
        ->call('toggleRule', $rule->id)
        ->assertHasNoErrors()
        ->call('toggleRule', $rule->id)
        ->assertHasNoErrors()
        ->call('deleteRule', $rule->id)
        ->assertHasNoErrors();

    expect(TodoRecurrenceRule::query()->count())->toBe(0);
});

test('paused recurrence rule cannot be reenabled when its task is no longer active', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->archived()->create();
    $rule = TodoRecurrenceRule::factory()->forTodo($todo)->paused()->create([
        'starts_on' => today()->toDateString(),
    ]);

    Livewire::actingAs($user)
        ->test(RecurringRules::class)
        ->call('toggleRule', $rule->id)
        ->assertHasErrors('recurrenceRule');

    expect($rule->refresh()->is_enabled)->toBeFalse();
});

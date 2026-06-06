<?php

use App\Actions\Todos\DeleteTodoRecurrenceRule;
use App\Actions\Todos\SaveTodoRecurrenceRule;
use App\Data\Todos\RecurrenceRuleData;
use App\Enums\RecurrenceEndType;
use App\Enums\RecurrenceFrequency;
use App\Enums\RecurrenceWeekday;
use App\Livewire\Todos\Show;
use App\Models\Todo;
use App\Models\TodoRecurrenceRule;
use App\Models\User;
use App\Queries\Todos\TodoRecurrenceRuleQuery;
use App\Rules\Todos\OwnedActiveTodo;
use App\Rules\Todos\RecurrenceRule;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\TodoRecurrenceRuleSeeder;
use Database\Seeders\TodoSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('recurrence rule data normalizes enabled weekly and paused monthly payloads', function () {
    $weekly = RecurrenceRuleData::fromPayload([
        'frequency' => RecurrenceFrequency::Weekly->value,
        'interval' => '1',
        'starts_on' => today()->toDateString(),
        'weekdays' => [RecurrenceWeekday::Friday->value, RecurrenceWeekday::Monday->value],
        'month_day' => '',
        'end_type' => RecurrenceEndType::Never->value,
        'ends_on' => '',
        'max_occurrences' => '',
        'is_enabled' => true,
    ]);

    $monthly = RecurrenceRuleData::fromPayload([
        'frequency' => RecurrenceFrequency::Monthly->value,
        'interval' => '2',
        'starts_on' => today()->toDateString(),
        'weekdays' => [RecurrenceWeekday::Friday->value],
        'month_day' => '15',
        'end_type' => RecurrenceEndType::AfterOccurrences->value,
        'ends_on' => '',
        'max_occurrences' => '6',
        'is_enabled' => false,
    ]);

    expect($weekly->frequency)->toBe(RecurrenceFrequency::Weekly)
        ->and($weekly->weekdays)->toBe([RecurrenceWeekday::Monday->value, RecurrenceWeekday::Friday->value])
        ->and($weekly->monthDay)->toBeNull()
        ->and($monthly->frequency)->toBe(RecurrenceFrequency::Monthly)
        ->and($monthly->weekdays)->toBe([])
        ->and($monthly->monthDay)->toBe(15)
        ->and($monthly->maxOccurrences)->toBe(6)
        ->and($monthly->isEnabled)->toBeFalse();
});

test('custom recurrence rule and active task rule validate success and failure cases', function () {
    $user = User::factory()->create();
    $activeTodo = Todo::factory()->for($user)->active()->create();
    $completedTodo = Todo::factory()->for($user)->completed()->create();

    $validPayload = [
        'frequency' => RecurrenceFrequency::Daily->value,
        'interval' => '1',
        'starts_on' => today()->toDateString(),
        'weekdays' => [],
        'month_day' => '',
        'end_type' => RecurrenceEndType::Never->value,
        'ends_on' => '',
        'max_occurrences' => '',
        'is_enabled' => true,
    ];

    expect(Validator::make(['rule' => $validPayload], ['rule' => [new RecurrenceRule]])->passes())->toBeTrue()
        ->and(Validator::make(['todo' => $activeTodo->id], ['todo' => [new OwnedActiveTodo($user)]])->passes())->toBeTrue()
        ->and(Validator::make(['todo' => $completedTodo->id], ['todo' => [new OwnedActiveTodo($user)]])->fails())->toBeTrue()
        ->and(Validator::make(['rule' => [...$validPayload, 'interval' => '31']], ['rule' => [new RecurrenceRule]])->fails())->toBeTrue();
});

test('recurrence data throws translated validation messages for invalid payloads', function () {
    expect(fn () => RecurrenceRuleData::fromPayload([
        'frequency' => RecurrenceFrequency::Weekly->value,
        'interval' => '1',
        'starts_on' => today()->toDateString(),
        'weekdays' => [],
        'month_day' => '',
        'end_type' => RecurrenceEndType::Never->value,
        'ends_on' => '',
        'max_occurrences' => '',
        'is_enabled' => true,
    ]))->toThrow(ValidationException::class, __('todos.validation.recurrence_weekdays'));
});

test('recurrence seeder adds idempotent demo rules for seeded demo users', function () {
    $this->seed([DemoUserSeeder::class, TodoSeeder::class, TodoRecurrenceRuleSeeder::class]);

    $users = User::query()->whereIn('email', ['test@example.com', 'second@example.com'])->get();

    expect($users)->toHaveCount(2);

    $users->each(function (User $user): void {
        expect($user->todoRecurrenceRules()->count())->toBe(3)
            ->and($user->todoRecurrenceRules()->where('is_enabled', false)->count())->toBe(1);
    });

    $count = TodoRecurrenceRule::query()->count();

    $this->seed(TodoRecurrenceRuleSeeder::class);

    expect(TodoRecurrenceRule::query()->count())->toBe($count);
});

test('task detail saves updates and clears an owner scoped recurrence rule', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->dueOn('2026-06-10')->create(['title' => 'Run weekly review']);

    Livewire::actingAs($user)
        ->test(Show::class, ['todo' => $todo->id])
        ->assertSee(__('todos.recurrence.heading'))
        ->assertSee(__('todos.recurrence.empty.title'))
        ->assertSet('recurrenceStartsOn', '2026-06-10')
        ->set('recurrenceFrequency', RecurrenceFrequency::Weekly->value)
        ->set('recurrenceInterval', '2')
        ->set('recurrenceWeekdays', [RecurrenceWeekday::Monday->value, RecurrenceWeekday::Wednesday->value])
        ->set('recurrenceEndType', RecurrenceEndType::AfterOccurrences->value)
        ->set('recurrenceMaxOccurrences', '8')
        ->call('saveRecurrenceRule')
        ->assertHasNoErrors()
        ->assertSee(__('todos.recurrence.summary_heading'));

    $rule = TodoRecurrenceRule::query()->sole();

    expect($rule->isOwnedBy($user))->toBeTrue()
        ->and($rule->todo->is($todo))->toBeTrue()
        ->and($rule->frequency)->toBe(RecurrenceFrequency::Weekly)
        ->and($rule->interval)->toBe(2)
        ->and($rule->weekdays)->toBe([RecurrenceWeekday::Monday->value, RecurrenceWeekday::Wednesday->value])
        ->and($rule->end_type)->toBe(RecurrenceEndType::AfterOccurrences)
        ->and($rule->max_occurrences)->toBe(8);

    Livewire::actingAs($user)
        ->test(Show::class, ['todo' => $todo->id])
        ->set('recurrenceFrequency', RecurrenceFrequency::Daily->value)
        ->set('recurrenceInterval', '3')
        ->set('recurrenceEndType', RecurrenceEndType::Never->value)
        ->call('saveRecurrenceRule')
        ->assertHasNoErrors();

    expect(TodoRecurrenceRule::query()->count())->toBe(1)
        ->and($rule->refresh()->frequency)->toBe(RecurrenceFrequency::Daily)
        ->and($rule->interval)->toBe(3)
        ->and($rule->weekdays)->toBe([])
        ->and($rule->max_occurrences)->toBeNull();

    Livewire::actingAs($user)
        ->test(Show::class, ['todo' => $todo->id])
        ->call('clearRecurrenceRule')
        ->assertHasNoErrors()
        ->assertSee(__('todos.recurrence.empty.title'));

    expect(TodoRecurrenceRule::query()->count())->toBe(0);
});

test('recurrence actions and queries keep private rules inside the owner boundary', function () {
    $viewer = User::factory()->create();
    $owner = User::factory()->create();
    $ownTodo = Todo::factory()->for($viewer)->create(['title' => 'Owner repeat task']);
    $foreignTodo = Todo::factory()->for($owner)->create(['title' => 'Foreign repeat task']);
    TodoRecurrenceRule::factory()->forTodo($foreignTodo)->weekly()->create();

    $data = RecurrenceRuleData::fromPayload([
        'frequency' => RecurrenceFrequency::Daily->value,
        'interval' => '1',
        'starts_on' => today()->toDateString(),
        'weekdays' => [],
        'month_day' => '',
        'end_type' => RecurrenceEndType::Never->value,
        'ends_on' => '',
        'max_occurrences' => '',
        'is_enabled' => true,
    ]);

    expect(fn () => app(SaveTodoRecurrenceRule::class)->handle($viewer, $foreignTodo, $data))
        ->toThrow(AuthorizationException::class);

    app(SaveTodoRecurrenceRule::class)->handle($viewer, $ownTodo, $data);

    $rules = app(TodoRecurrenceRuleQuery::class)->activeFor($viewer);

    expect($rules)->toHaveCount(1)
        ->and($rules->first()->todo->title)->toBe('Owner repeat task')
        ->and($rules->pluck('todo.title')->all())->not->toContain('Foreign repeat task');

    Livewire::actingAs($viewer)
        ->test(Show::class, ['todo' => $ownTodo->id])
        ->assertSee(__('todos.recurrence.summary_heading'))
        ->assertDontSee('Foreign repeat task');
});

test('completed and archived tasks keep recurrence context locked from mutation', function () {
    $user = User::factory()->create();
    $completed = Todo::factory()->for($user)->completed()->create(['title' => 'Completed source']);
    $archived = Todo::factory()->for($user)->archived()->create(['title' => 'Archived source']);
    $rule = TodoRecurrenceRule::factory()->forTodo($archived)->weekly()->create();

    Livewire::actingAs($user)
        ->test(Show::class, ['todo' => $archived->id])
        ->assertSee(__('todos.recurrence.locked.heading'))
        ->assertSee($rule->summary())
        ->call('clearRecurrenceRule')
        ->assertHasNoErrors();

    expect($rule->fresh())->not->toBeNull();

    $data = RecurrenceRuleData::fromPayload([
        'frequency' => RecurrenceFrequency::Daily->value,
        'interval' => '1',
        'starts_on' => today()->toDateString(),
        'weekdays' => [],
        'month_day' => '',
        'end_type' => RecurrenceEndType::Never->value,
        'ends_on' => '',
        'max_occurrences' => '',
        'is_enabled' => true,
    ]);

    expect(fn () => app(SaveTodoRecurrenceRule::class)->handle($user, $completed, $data))
        ->toThrow(ValidationException::class);

    expect(fn () => app(DeleteTodoRecurrenceRule::class)->handle($user, $archived))
        ->toThrow(ValidationException::class);
});

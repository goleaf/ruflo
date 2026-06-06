<?php

use App\Actions\Todos\ArchiveTodo;
use App\Actions\Todos\CompleteTodo;
use App\Actions\Todos\DeleteTodo;
use App\Actions\Todos\GenerateRecurringOccurrences;
use App\Actions\Todos\UpdateTodo;
use App\Data\Todos\TodoData;
use App\Enums\Priority;
use App\Enums\RecurrenceFrequency;
use App\Enums\ReminderStatus;
use App\Livewire\Todos\RecurringRules;
use App\Models\Reminder;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\TodoRecurrenceRule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

test('recurring occurrence generation creates private idempotent task copies with reminders', function () {
    $this->travelTo('2026-06-06 09:00:00');

    $user = User::factory()->create();
    $other = User::factory()->create();
    $tag = Tag::factory()->for($user)->create(['name' => 'Reports']);
    $source = Todo::factory()
        ->for($user)
        ->forTag($tag)
        ->dueOn('2026-06-06')
        ->highPriority()
        ->active()
        ->create(['title' => 'Publish metrics']);
    $foreignSource = Todo::factory()->for($other)->dueOn('2026-06-06')->active()->create(['title' => 'Foreign metrics']);

    Reminder::factory()->forTodo($source)->future(CarbonImmutable::parse('2026-06-06 08:00:00'))->create();
    TodoChecklistItem::factory()->forTodo($source)->position(2)->completed()->create(['title' => 'Draft metrics']);
    TodoChecklistItem::factory()->forTodo($source)->position(1)->pending()->create(['title' => 'Gather metrics']);

    $rule = TodoRecurrenceRule::factory()->forTodo($source)->create([
        'frequency' => RecurrenceFrequency::Daily,
        'interval' => 1,
        'starts_on' => '2026-06-06',
    ]);
    TodoRecurrenceRule::factory()->forTodo($foreignSource)->create([
        'frequency' => RecurrenceFrequency::Daily,
        'interval' => 1,
        'starts_on' => '2026-06-06',
    ]);

    $result = app(GenerateRecurringOccurrences::class)->handle($user, CarbonImmutable::parse('2026-06-09'));

    expect($result->matchedCount)->toBe(1)
        ->and($result->processedRuleCount)->toBe(1)
        ->and($result->createdCount)->toBe(3)
        ->and($result->remainingCount)->toBe(0);

    $generated = Todo::query()
        ->where('recurrence_rule_id', $rule->id)
        ->orderBy('recurrence_occurs_on')
        ->get();

    expect($generated)->toHaveCount(3)
        ->and($generated->pluck('user_id')->unique()->all())->toBe([$user->id])
        ->and($generated->pluck('recurrence_source_todo_id')->unique()->all())->toBe([$source->id])
        ->and($generated->pluck('recurrence_occurs_on')->map->toDateString()->all())->toBe([
            '2026-06-07',
            '2026-06-08',
            '2026-06-09',
        ])
        ->and($generated->pluck('priority')->all())->each->toBe(Priority::High);

    $generated->each(function (Todo $todo) use ($tag): void {
        expect($todo->tags()->pluck('tags.id')->all())->toBe([$tag->id])
            ->and($todo->checklistItems()->pluck('title')->all())->toBe(['Gather metrics', 'Draft metrics'])
            ->and($todo->checklistItems()->pluck('is_completed')->all())->toBe([false, false])
            ->and($todo->isGeneratedOccurrence())->toBeTrue();
    });

    $generatedReminders = Reminder::query()
        ->whereIn('todo_id', $generated->pluck('id'))
        ->orderBy('remind_at')
        ->get();

    expect($generatedReminders)->toHaveCount(3)
        ->and($generatedReminders->pluck('status')->all())->each->toBe(ReminderStatus::Pending)
        ->and($generatedReminders->pluck('remind_at')->map->format('Y-m-d H:i')->all())->toBe([
            '2026-06-07 08:00',
            '2026-06-08 08:00',
            '2026-06-09 08:00',
        ]);

    $repeat = app(GenerateRecurringOccurrences::class)->handle($user, CarbonImmutable::parse('2026-06-09'));

    expect($repeat->createdCount)->toBe(0)
        ->and(Todo::query()->where('recurrence_rule_id', $rule->id)->count())->toBe(3)
        ->and(Todo::query()->where('user_id', $other->id)->whereNotNull('recurrence_rule_id')->count())->toBe(0);
});

test('generated recurring occurrence can be completed and edited without changing the source series', function () {
    $this->travelTo('2026-06-06 09:00:00');

    $user = User::factory()->create();
    $source = Todo::factory()->for($user)->dueOn('2026-06-06')->active()->create([
        'title' => 'Standup notes',
        'priority' => Priority::Normal,
    ]);
    $rule = TodoRecurrenceRule::factory()->forTodo($source)->afterOccurrences(1)->create([
        'frequency' => RecurrenceFrequency::Daily,
        'starts_on' => '2026-06-06',
    ]);

    app(GenerateRecurringOccurrences::class)->handle($user, CarbonImmutable::parse('2026-06-07'));

    $occurrence = $rule->occurrences()->firstOrFail();

    app(CompleteTodo::class)->handle($occurrence);
    app(UpdateTodo::class)->handle($user, $occurrence->refresh(), new TodoData(
        title: 'Edited occurrence only',
        priority: Priority::Urgent,
        dueDate: '2026-06-08',
    ));

    expect($occurrence->refresh()->is_completed)->toBeTrue()
        ->and($occurrence->title)->toBe('Edited occurrence only')
        ->and($occurrence->priority)->toBe(Priority::Urgent)
        ->and($occurrence->due_date->toDateString())->toBe('2026-06-08')
        ->and($source->refresh()->is_completed)->toBeFalse()
        ->and($source->title)->toBe('Standup notes')
        ->and($source->priority)->toBe(Priority::Normal)
        ->and($source->due_date->toDateString())->toBe('2026-06-06')
        ->and($rule->refresh()->is_enabled)->toBeTrue();
});

test('soft deleted generated occurrence is treated as skipped and is not recreated', function () {
    $this->travelTo('2026-06-06 09:00:00');

    $user = User::factory()->create();
    $source = Todo::factory()->for($user)->dueOn('2026-06-06')->active()->create(['title' => 'Review backups']);
    $rule = TodoRecurrenceRule::factory()->forTodo($source)->afterOccurrences(2)->create([
        'frequency' => RecurrenceFrequency::Daily,
        'starts_on' => '2026-06-06',
    ]);

    app(GenerateRecurringOccurrences::class)->handle($user, CarbonImmutable::parse('2026-06-07'));

    $occurrence = $rule->occurrences()->firstOrFail();
    app(DeleteTodo::class)->handle($occurrence);

    $rule->forceFill(['last_generated_until' => null])->save();
    $repeat = app(GenerateRecurringOccurrences::class)->handle($user, CarbonImmutable::parse('2026-06-07'));

    expect($repeat->createdCount)->toBe(0)
        ->and(Todo::withTrashed()->where('recurrence_rule_id', $rule->id)->count())->toBe(1)
        ->and(Todo::query()->where('recurrence_rule_id', $rule->id)->count())->toBe(0);
});

test('archived and deleted source series do not generate future occurrences', function () {
    $this->travelTo('2026-06-06 09:00:00');

    $user = User::factory()->create();
    $archivedSource = Todo::factory()->for($user)->dueOn('2026-06-06')->active()->create(['title' => 'Archived series']);
    $deletedSource = Todo::factory()->for($user)->dueOn('2026-06-06')->active()->create(['title' => 'Deleted series']);

    app(ArchiveTodo::class)->handle($archivedSource);
    app(DeleteTodo::class)->handle($deletedSource);

    TodoRecurrenceRule::factory()->forTodo($archivedSource)->create([
        'frequency' => RecurrenceFrequency::Daily,
        'starts_on' => '2026-06-06',
    ]);
    TodoRecurrenceRule::factory()->forTodo($deletedSource)->create([
        'frequency' => RecurrenceFrequency::Daily,
        'starts_on' => '2026-06-06',
    ]);

    $result = app(GenerateRecurringOccurrences::class)->handle($user, CarbonImmutable::parse('2026-06-09'));

    expect($result->matchedCount)->toBe(0)
        ->and($result->createdCount)->toBe(0)
        ->and(Todo::query()->whereNotNull('recurrence_rule_id')->count())->toBe(0);
});

test('recurring occurrence generation is bounded and resumable through manual web chunks', function () {
    $this->travelTo('2026-06-06 09:00:00');
    config(['hosting.web_processing.chunk_size' => 1]);

    $user = User::factory()->create();
    $firstTodo = Todo::factory()->for($user)->dueOn('2026-06-06')->active()->create(['title' => 'First series']);
    $secondTodo = Todo::factory()->for($user)->dueOn('2026-06-06')->active()->create(['title' => 'Second series']);

    TodoRecurrenceRule::factory()->forTodo($firstTodo)->afterOccurrences(1)->create([
        'frequency' => RecurrenceFrequency::Daily,
        'starts_on' => '2026-06-06',
    ]);
    TodoRecurrenceRule::factory()->forTodo($secondTodo)->afterOccurrences(1)->create([
        'frequency' => RecurrenceFrequency::Daily,
        'starts_on' => '2026-06-06',
    ]);

    $firstRun = app(GenerateRecurringOccurrences::class)->handle($user, CarbonImmutable::parse('2026-06-07'));

    expect($firstRun->matchedCount)->toBe(2)
        ->and($firstRun->processedRuleCount)->toBe(1)
        ->and($firstRun->createdCount)->toBe(1)
        ->and($firstRun->remainingCount)->toBe(1)
        ->and(Todo::query()->whereNotNull('recurrence_rule_id')->count())->toBe(1);

    $secondRun = app(GenerateRecurringOccurrences::class)->handle($user, CarbonImmutable::parse('2026-06-07'));

    expect($secondRun->matchedCount)->toBe(1)
        ->and($secondRun->processedRuleCount)->toBe(1)
        ->and($secondRun->createdCount)->toBe(1)
        ->and($secondRun->remainingCount)->toBe(0)
        ->and(Todo::query()->whereNotNull('recurrence_rule_id')->count())->toBe(2);
});

test('recurring rules page triggers generation and reports owner scoped progress', function () {
    $this->travelTo('2026-06-06 09:00:00');

    $user = User::factory()->create();
    $other = User::factory()->create();
    $todo = Todo::factory()->for($user)->dueOn('2026-06-06')->active()->create(['title' => 'Owner visible series']);
    $foreignTodo = Todo::factory()->for($other)->dueOn('2026-06-06')->active()->create(['title' => 'Foreign hidden series']);

    TodoRecurrenceRule::factory()->forTodo($todo)->afterOccurrences(2)->create([
        'frequency' => RecurrenceFrequency::Daily,
        'starts_on' => '2026-06-06',
    ]);
    TodoRecurrenceRule::factory()->forTodo($foreignTodo)->afterOccurrences(2)->create([
        'frequency' => RecurrenceFrequency::Daily,
        'starts_on' => '2026-06-06',
    ]);

    Livewire::actingAs($user)
        ->test(RecurringRules::class)
        ->assertSee(__('todos.recurrence.generation.web_mode.heading'))
        ->call('generateOccurrences')
        ->assertHasNoErrors()
        ->assertSet('lastGenerationReport.created', 2)
        ->assertSee(__('todos.recurrence.generation.report_heading'));

    expect(Todo::query()->where('user_id', $user->id)->whereNotNull('recurrence_rule_id')->count())->toBe(2)
        ->and(Todo::query()->where('user_id', $other->id)->whereNotNull('recurrence_rule_id')->count())->toBe(0);
});

test('todo factory can create a generated recurring occurrence state', function () {
    $user = User::factory()->create();
    $source = Todo::factory()->for($user)->dueOn('2026-06-06')->active()->create(['title' => 'Factory source']);
    $rule = TodoRecurrenceRule::factory()->forTodo($source)->create(['starts_on' => '2026-06-06']);

    $occurrence = Todo::factory()->generatedOccurrence($rule, '2026-06-07')->create();

    expect($occurrence->isOwnedBy($user))->toBeTrue()
        ->and($occurrence->isGeneratedOccurrence())->toBeTrue()
        ->and($occurrence->generatedRecurrenceRule->is($rule))->toBeTrue()
        ->and($occurrence->recurrenceSource->is($source))->toBeTrue()
        ->and($occurrence->recurrence_occurs_on->toDateString())->toBe('2026-06-07');
});

<?php

use App\Actions\Reminders\ProcessDueReminders;
use App\Enums\ReminderStatus;
use App\Livewire\Todos\Reminders as TodoReminders;
use App\Models\Reminder;
use App\Models\Todo;
use App\Models\User;
use App\Rules\Reminders\ReminderAt;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;

test('reminders route renders owner scoped web mode controls', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $todo = Todo::factory()->for($user)->active()->create(['title' => 'Owner visible reminder task']);
    $foreignTodo = Todo::factory()->for($other)->active()->create(['title' => 'Foreign hidden reminder task']);

    Reminder::factory()->forTodo($todo)->future(now()->addDay())->create();
    Reminder::factory()->forTodo($foreignTodo)->future(now()->addDay())->create();

    $this->actingAs($user)
        ->get(route('todos.reminders'))
        ->assertOk()
        ->assertSeeText(__('reminders.pages.index.title'))
        ->assertSeeText('Owner visible reminder task')
        ->assertDontSeeText('Foreign hidden reminder task')
        ->assertSee('data-test="reminder-web-mode-note"', false)
        ->assertSee('data-test="reminder-preferences"', false)
        ->assertSee('data-test="local-browser-notifications"', false)
        ->assertSee('data-test="reminder-schedule"', false)
        ->assertSee('data-test="reminder-list"', false);
});

test('reminders page includes alpine only local browser notification controls', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->active()->create(['title' => 'Browser alert reminder task']);
    $script = file_get_contents(resource_path('js/app.js'));

    Reminder::factory()->forTodo($todo)->future(now()->addDay())->create();

    $this->actingAs($user)
        ->get(route('todos.reminders'))
        ->assertOk()
        ->assertSee('data-test="local-browser-notifications"', false)
        ->assertSee('window.RuFlo.localReminderNotifications', false)
        ->assertDontSee('Notification.requestPermission', false)
        ->assertDontSee('new Notification', false)
        ->assertDontSee('ruflo:local-reminders', false)
        ->assertDontSee('navigator.serviceWorker', false)
        ->assertDontSee('PushManager', false);

    expect($script)
        ->toContain('window.RuFlo')
        ->toContain('window.RuFlo.localReminderNotifications')
        ->toContain('Notification.requestPermission')
        ->toContain('new Notification')
        ->toContain('ruflo:local-reminders')
        ->not->toContain('window.Alpine.data')
        ->not->toContain('navigator.serviceWorker')
        ->not->toContain('PushManager');
});

test('local browser notification payload stays owner scoped and pending only', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $todo = Todo::factory()->for($user)->active()->create(['title' => 'Local browser reminder task']);
    $processedTodo = Todo::factory()->for($user)->active()->create(['title' => 'Processed local reminder task']);
    $archivedTodo = Todo::factory()->for($user)->archived()->create(['title' => 'Archived local reminder task']);
    $foreignTodo = Todo::factory()->for($other)->active()->create(['title' => 'Foreign local reminder task']);

    Reminder::factory()->forTodo($todo)->future(now()->addDay())->create();
    Reminder::factory()->forTodo($processedTodo)->processed()->create();
    Reminder::factory()->forTodo($archivedTodo)->future(now()->addDay())->create();
    Reminder::factory()->forTodo($foreignTodo)->future(now()->addDay())->create();

    $payload = Livewire::actingAs($user)
        ->test(TodoReminders::class)
        ->instance()
        ->localNotificationReminders();

    expect($payload)->toHaveCount(1)
        ->and($payload[0]['title'])->toBe(__('reminders.notifications.todo_due.title'))
        ->and($payload[0]['body'])->toContain('Local browser reminder task')
        ->and($payload[0]['url'])->toBe(route('todos.show', $todo))
        ->and($payload[0]['tag'])->toStartWith('ruflo-reminder-');
});

test('reminder scheduling validates owner tasks and reminder time', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $todo = Todo::factory()->for($user)->active()->create(['title' => 'Schedule this task']);
    $archivedTodo = Todo::factory()->for($user)->archived()->create();
    $foreignTodo = Todo::factory()->for($other)->active()->create();
    $futureReminderAt = now()->addDay()->startOfMinute()->format('Y-m-d\TH:i');

    Livewire::actingAs($user)
        ->test(TodoReminders::class)
        ->call('scheduleReminder')
        ->assertHasErrors(['todoId']);

    Livewire::actingAs($user)
        ->test(TodoReminders::class)
        ->set('todoId', (string) $todo->id)
        ->set('remindAt', '')
        ->call('scheduleReminder')
        ->assertHasErrors(['remindAt']);

    Livewire::actingAs($user)
        ->test(TodoReminders::class)
        ->set('todoId', (string) $foreignTodo->id)
        ->set('remindAt', $futureReminderAt)
        ->call('scheduleReminder')
        ->assertHasErrors(['todoId']);

    Livewire::actingAs($user)
        ->test(TodoReminders::class)
        ->set('todoId', (string) $archivedTodo->id)
        ->set('remindAt', $futureReminderAt)
        ->call('scheduleReminder')
        ->assertHasErrors(['todo_id']);

    Livewire::actingAs($user)
        ->test(TodoReminders::class)
        ->set('todoId', (string) $todo->id)
        ->set('remindAt', 'not-a-browser-datetime')
        ->call('scheduleReminder')
        ->assertHasErrors(['remindAt']);

    $pastValidator = Validator::make(
        ['remindAt' => now()->subHour()->format('Y-m-d\TH:i')],
        ['remindAt' => [new ReminderAt]],
    );

    expect($pastValidator->fails())->toBeTrue();

    Livewire::actingAs($user)
        ->test(TodoReminders::class)
        ->set('todoId', (string) $todo->id)
        ->set('remindAt', $futureReminderAt)
        ->call('scheduleReminder')
        ->assertHasNoErrors()
        ->assertSet('todoId', '');

    $reminder = Reminder::query()
        ->ownedBy($user)
        ->where('todo_id', $todo->id)
        ->sole();

    expect($reminder->status)->toBe(ReminderStatus::Pending)
        ->and($reminder->remind_at->format('Y-m-d H:i'))->toBe(str_replace('T', ' ', $futureReminderAt));
});

test('due reminders process through bounded web chunks and database notifications', function () {
    config([
        'hosting.web_processing.chunk_size' => 2,
        'hosting.web_processing.detail_limit' => 5,
    ]);

    $user = User::factory()->create();
    $other = User::factory()->create();
    $todos = Todo::factory()->for($user)->active()->count(3)->create();
    $foreignTodo = Todo::factory()->for($other)->active()->create();

    $todos->each(fn (Todo $todo) => Reminder::factory()->forTodo($todo)->due(now()->subMinutes(5))->create());
    Reminder::factory()->forTodo($foreignTodo)->due(now()->subMinutes(5))->create();

    $firstRun = app(ProcessDueReminders::class)->handle($user);

    expect($firstRun->matchedCount)->toBe(3)
        ->and($firstRun->processedCount)->toBe(2)
        ->and($firstRun->skippedCount)->toBe(0)
        ->and($firstRun->remainingCount)->toBe(1)
        ->and($user->notifications()->count())->toBe(2)
        ->and(Reminder::query()->ownedBy($user)->where('status', ReminderStatus::Processed)->count())->toBe(2)
        ->and(Reminder::query()->ownedBy($user)->where('status', ReminderStatus::Pending)->count())->toBe(1)
        ->and(Reminder::query()->ownedBy($other)->where('status', ReminderStatus::Pending)->count())->toBe(1);

    $secondRun = app(ProcessDueReminders::class)->handle($user);

    expect($secondRun->matchedCount)->toBe(1)
        ->and($secondRun->processedCount)->toBe(1)
        ->and($secondRun->remainingCount)->toBe(0)
        ->and($user->notifications()->count())->toBe(3)
        ->and($other->notifications()->count())->toBe(0)
        ->and(Reminder::query()->ownedBy($user)->where('status', ReminderStatus::Processed)->count())->toBe(3);
});

test('completed archived and deleted task reminders are skipped without notifying', function () {
    $user = User::factory()->create();
    $completed = Todo::factory()->for($user)->completed()->create();
    $archived = Todo::factory()->for($user)->archived()->create();
    $deleted = Todo::factory()->for($user)->deleted()->create();

    collect([$completed, $archived, $deleted])
        ->each(fn (Todo $todo) => Reminder::factory()->forTodo($todo)->due(now()->subMinutes(10))->create());

    $result = app(ProcessDueReminders::class)->handle($user);

    expect($result->matchedCount)->toBe(3)
        ->and($result->processedCount)->toBe(0)
        ->and($result->skippedCount)->toBe(3)
        ->and($result->failedCount)->toBe(0)
        ->and($user->notifications()->count())->toBe(0)
        ->and(Reminder::query()->ownedBy($user)->where('status', ReminderStatus::Skipped)->count())->toBe(3)
        ->and(Reminder::query()->ownedBy($user)->where('skipped_reason', 'task_not_actionable')->count())->toBe(3);
});

test('disabled reminder preference skips due reminders without email or worker dependency', function () {
    $user = User::factory()->create(['reminders_enabled' => false]);
    $todo = Todo::factory()->for($user)->active()->create();
    $reminder = Reminder::factory()->forTodo($todo)->due(now()->subMinutes(5))->create();

    $result = app(ProcessDueReminders::class)->handle($user);

    expect($result->matchedCount)->toBe(1)
        ->and($result->processedCount)->toBe(0)
        ->and($result->skippedCount)->toBe(1)
        ->and($user->notifications()->count())->toBe(0)
        ->and($reminder->refresh()->status)->toBe(ReminderStatus::Skipped)
        ->and($reminder->skipped_reason)->toBe('preferences_disabled')
        ->and(config('queue.default'))->toBe('sync')
        ->and(config('hosting.restricted'))->toBeTrue();
});

test('dashboard opening processes due reminders for the authenticated user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $todo = Todo::factory()->for($user)->active()->create(['title' => 'Dashboard due reminder']);
    $foreignTodo = Todo::factory()->for($other)->active()->create();
    $reminder = Reminder::factory()->forTodo($todo)->due(now()->subMinutes(5))->create();
    $foreignReminder = Reminder::factory()->forTodo($foreignTodo)->due(now()->subMinutes(5))->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="dashboard-reminder-run-report"', false)
        ->assertSeeText(__('reminders.processing.report_heading'));

    expect($reminder->refresh()->status)->toBe(ReminderStatus::Processed)
        ->and($foreignReminder->refresh()->status)->toBe(ReminderStatus::Pending)
        ->and($user->notifications()->count())->toBe(1)
        ->and($other->notifications()->count())->toBe(0);
});

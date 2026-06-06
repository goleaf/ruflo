<?php

use App\Actions\Todos\PausePomodoroSession;
use App\Actions\Todos\StartPomodoroSession;
use App\Enums\PomodoroSessionStatus;
use App\Livewire\Todos\Focus;
use App\Models\PomodoroSession;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('pomodoro sessions start pause resume and complete from focus mode', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-10 09:00:00', config('app.timezone')));

    try {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->focusCandidate()->create(['title' => 'Write the launch note']);

        $component = Livewire::actingAs($user)
            ->test(Focus::class)
            ->set('durationMinutes', '15')
            ->call('selectTask', $todo->id)
            ->call('startFocusSession')
            ->assertHasNoErrors()
            ->assertSet('selectedTodoId', $todo->id);

        $session = PomodoroSession::query()->sole();

        expect($session->isOwnedBy($user))->toBeTrue()
            ->and($session->todo->is($todo))->toBeTrue()
            ->and($session->duration_minutes)->toBe(15)
            ->and($session->status)->toBe(PomodoroSessionStatus::Running)
            ->and($session->remainingSeconds())->toBe(900);

        Carbon::setTestNow(Carbon::parse('2026-03-10 09:05:00', config('app.timezone')));

        $component->call('pauseFocusSession')->assertHasNoErrors();

        $session->refresh();

        expect($session->status)->toBe(PomodoroSessionStatus::Paused)
            ->and($session->elapsed_seconds)->toBe(300)
            ->and($session->remainingSeconds())->toBe(600);

        Carbon::setTestNow(Carbon::parse('2026-03-10 09:07:00', config('app.timezone')));

        $component->call('resumeFocusSession')->assertHasNoErrors();

        $session->refresh();

        expect($session->status)->toBe(PomodoroSessionStatus::Running)
            ->and($session->elapsed_seconds)->toBe(300)
            ->and($session->last_started_at)->not->toBeNull();

        Carbon::setTestNow(Carbon::parse('2026-03-10 09:12:00', config('app.timezone')));

        $component->call('completeFocusSession')->assertHasNoErrors();

        $session->refresh();

        expect($session->status)->toBe(PomodoroSessionStatus::Completed)
            ->and($session->elapsed_seconds)->toBe(600)
            ->and($session->completed_at)->not->toBeNull()
            ->and($session->last_started_at)->toBeNull();
    } finally {
        Carbon::setTestNow();
    }
});

test('pomodoro start validates duration and blocks duplicate active sessions', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->focusCandidate()->create();

    Livewire::actingAs($user)
        ->test(Focus::class)
        ->call('selectTask', $todo->id)
        ->set('durationMinutes', '10')
        ->call('startFocusSession')
        ->assertHasErrors('durationMinutes');

    expect(PomodoroSession::query()->count())->toBe(0);

    Livewire::actingAs($user)
        ->test(Focus::class)
        ->call('selectTask', $todo->id)
        ->set('durationMinutes', '25')
        ->call('startFocusSession')
        ->assertHasNoErrors()
        ->call('startFocusSession')
        ->assertHasErrors('durationMinutes');

    expect(PomodoroSession::query()->count())->toBe(1);
});

test('pomodoro actions keep private ownership and focus boundaries', function () {
    $viewer = User::factory()->create();
    $owner = User::factory()->create();
    $foreignTodo = Todo::factory()->for($owner)->focusCandidate()->create();
    $foreignSession = PomodoroSession::factory()->forTodo($foreignTodo)->running()->create();
    $notFocus = Todo::factory()->for($viewer)->create();

    expect(fn () => app(PausePomodoroSession::class)->handle($viewer, $foreignSession))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(StartPomodoroSession::class)->handle($viewer, $notFocus, 25))
        ->toThrow(ModelNotFoundException::class);

    Livewire::actingAs($viewer)
        ->test(Focus::class)
        ->call('pauseFocusSession')
        ->assertHasErrors('session');
});

test('quick task actions close linked pomodoro sessions', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-10 09:00:00', config('app.timezone')));

    try {
        $user = User::factory()->create();
        $completeTodo = Todo::factory()->for($user)->focusCandidate()->create(['title' => 'Finish the brief']);
        $deferTodo = Todo::factory()->for($user)->urgentPriority()->dueOn('2026-03-10')->create(['title' => 'Move the call']);

        $completeSession = PomodoroSession::factory()->forTodo($completeTodo)->running()->create();
        $deferSession = PomodoroSession::factory()->forTodo($deferTodo)->paused()->create();

        Livewire::actingAs($user)
            ->test(Focus::class)
            ->call('completeTodo', $completeTodo->id)
            ->assertHasNoErrors();

        expect($completeTodo->refresh()->is_completed)->toBeTrue()
            ->and($completeSession->refresh()->status)->toBe(PomodoroSessionStatus::Completed);

        Livewire::actingAs($user)
            ->test(Focus::class)
            ->call('deferTodo', $deferTodo->id)
            ->assertHasNoErrors();

        expect($deferTodo->refresh()->due_date->toDateString())->toBe('2026-03-11')
            ->and($deferSession->refresh()->status)->toBe(PomodoroSessionStatus::Abandoned);
    } finally {
        Carbon::setTestNow();
    }
});

test('focus page renders persisted timer controls without background assumptions', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->focusCandidate()->create(['title' => 'Draft the quiet plan']);
    PomodoroSession::factory()->forTodo($todo)->paused()->create();

    $response = $this->actingAs($user)->get(route('todos.focus'));
    $view = file_get_contents(resource_path('views/livewire/todos/focus.blade.php'));
    $source = file_get_contents(app_path('Livewire/Todos/Focus.php'));

    $response->assertOk()
        ->assertSee(__('todos.focus.timer.heading'))
        ->assertSee('Draft the quiet plan')
        ->assertSee(__('todos.focus.timer.status.paused'))
        ->assertSee(__('todos.focus.timer.resume'))
        ->assertSee(__('todos.focus.timer.hosting_note'));

    expect($view)
        ->toContain('startFocusSession')
        ->toContain('pauseFocusSession')
        ->toContain('resumeFocusSession')
        ->toContain('completeFocusSession')
        ->toContain('abandonFocusSession')
        ->toContain('x-on:keydown.window')
        ->toContain('kbd="P"')
        ->not->toContain('wire:poll')
        ->not->toContain('@php')
        ->and($source)
        ->toContain('PomodoroSessionQuery')
        ->toContain('StartPomodoroSession')
        ->toContain('PomodoroDuration')
        ->not->toContain('PomodoroSession::query()');
});

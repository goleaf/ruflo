<?php

use App\Actions\Todos\CompletePomodoroSession;
use App\Actions\Todos\CreatePomodoroTimeEntry;
use App\Actions\Todos\DeleteTimeEntry;
use App\Actions\Todos\StartTimeEntryTimer;
use App\Enums\PomodoroSessionStatus;
use App\Enums\TimeEntrySource;
use App\Enums\TimeEntryStatus;
use App\Livewire\Todos\Time;
use App\Models\PomodoroSession;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('time tracking route renders manual and timer workflows without background assumptions', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->work()->create();
    Todo::factory()->forProject($project)->focusCandidate()->create(['title' => 'Draft the timing notes']);
    TimeEntry::factory()->forProject($project)->manual(25)->create(['notes' => 'Planned the next quiet work block.']);

    $response = $this->actingAs($user)->get(route('todos.time'));
    $view = file_get_contents(resource_path('views/livewire/todos/time.blade.php'));
    $source = file_get_contents(app_path('Livewire/Todos/Time.php'));

    $response->assertOk()
        ->assertSee(__('todos.pages.time.title'))
        ->assertSee(__('todos.time.timer.heading'))
        ->assertSee(__('todos.time.manual.heading'))
        ->assertSee('Draft the timing notes')
        ->assertSee('Planned the next quiet work block.')
        ->assertSee(__('todos.time.timer.hosting_note'));

    expect($view)
        ->toContain('startTimer')
        ->toContain('stopTimer')
        ->toContain('discardTimer')
        ->toContain('createManualEntry')
        ->toContain('deleteEntry')
        ->toContain('x-on:keydown.window')
        ->toContain('kbd="T"')
        ->not->toContain('wire:poll')
        ->not->toContain('@php')
        ->and($source)
        ->toContain('TimeEntryQuery')
        ->toContain('StartTimeEntryTimer')
        ->toContain('CreateManualTimeEntry')
        ->toContain('TimeEntryDuration')
        ->not->toContain('TimeEntry::query()')
        ->not->toContain('->save()');
});

test('manual time entries validate duration date and owner context', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-10 10:00:00', config('app.timezone')));

    try {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->work()->create();
        $todo = Todo::factory()->forProject($project)->create(['title' => 'Prepare the project notes']);

        Livewire::actingAs($user)
            ->test(Time::class)
            ->set('manualTodoId', (string) $todo->id)
            ->set('manualMinutes', '45')
            ->set('manualEntryDate', '2026-03-10')
            ->set('manualNotes', '  Drafted   project notes  ')
            ->call('createManualEntry')
            ->assertHasNoErrors();

        $entry = TimeEntry::query()->sole();

        expect($entry->isOwnedBy($user))->toBeTrue()
            ->and($entry->todo->is($todo))->toBeTrue()
            ->and($entry->project->is($project))->toBeTrue()
            ->and($entry->duration_seconds)->toBe(2700)
            ->and($entry->source)->toBe(TimeEntrySource::Manual)
            ->and($entry->status)->toBe(TimeEntryStatus::Completed)
            ->and($entry->notes)->toBe('Drafted project notes');

        Livewire::actingAs($user)
            ->test(Time::class)
            ->set('manualProjectId', (string) $project->id)
            ->set('manualMinutes', '0')
            ->set('manualEntryDate', '2026-03-10')
            ->call('createManualEntry')
            ->assertHasErrors('manualMinutes');

        Livewire::actingAs($user)
            ->test(Time::class)
            ->set('manualProjectId', (string) $project->id)
            ->set('manualMinutes', '30')
            ->set('manualEntryDate', '2026-03-11')
            ->call('createManualEntry')
            ->assertHasErrors('manualEntryDate');

        Livewire::actingAs($user)
            ->test(Time::class)
            ->set('manualMinutes', '30')
            ->set('manualEntryDate', '2026-03-10')
            ->call('createManualEntry')
            ->assertHasErrors('context');
    } finally {
        Carbon::setTestNow();
    }
});

test('time tracker starts stops and discards one active web timer', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-10 09:00:00', config('app.timezone')));

    try {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->work()->create();
        $todo = Todo::factory()->forProject($project)->create(['title' => 'Track the work block']);

        $component = Livewire::actingAs($user)
            ->test(Time::class)
            ->set('timerTodoId', (string) $todo->id)
            ->call('startTimer')
            ->assertHasNoErrors();

        $entry = TimeEntry::query()->sole();

        expect($entry->status)->toBe(TimeEntryStatus::Running)
            ->and($entry->source)->toBe(TimeEntrySource::Timer)
            ->and($entry->todo->is($todo))->toBeTrue()
            ->and($entry->project->is($project))->toBeTrue();

        $component
            ->set('timerTodoId', (string) $todo->id)
            ->call('startTimer')
            ->assertHasErrors('timer');

        Carbon::setTestNow(Carbon::parse('2026-03-10 09:37:00', config('app.timezone')));

        $component->call('stopTimer')->assertHasNoErrors();

        expect($entry->refresh()->status)->toBe(TimeEntryStatus::Completed)
            ->and($entry->duration_seconds)->toBe(2220)
            ->and($entry->stopped_at)->not->toBeNull();

        Carbon::setTestNow(Carbon::parse('2026-03-10 10:00:00', config('app.timezone')));

        $component
            ->set('timerProjectId', (string) $project->id)
            ->call('startTimer')
            ->assertHasNoErrors();

        $discarded = TimeEntry::query()->latest('id')->firstOrFail();

        Carbon::setTestNow(Carbon::parse('2026-03-10 10:05:00', config('app.timezone')));

        $component->call('discardTimer')->assertHasNoErrors();

        expect($discarded->refresh()->status)->toBe(TimeEntryStatus::Discarded)
            ->and(TimeEntry::query()->where('status', TimeEntryStatus::Completed->value)->count())->toBe(1);
    } finally {
        Carbon::setTestNow();
    }
});

test('time tracking keeps private ownership and trackable context boundaries', function () {
    $viewer = User::factory()->create();
    $owner = User::factory()->create();
    $foreignTodo = Todo::factory()->for($owner)->create();
    $foreignEntry = TimeEntry::factory()->forTodo($foreignTodo)->manual()->create();
    $archivedTodo = Todo::factory()->for($viewer)->archived()->create();

    Livewire::actingAs($viewer)
        ->test(Time::class)
        ->set('timerTodoId', (string) $foreignTodo->id)
        ->call('startTimer')
        ->assertHasErrors('timerTodoId');

    expect(fn () => app(StartTimeEntryTimer::class)->handle($viewer, $foreignTodo->id, null))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => app(StartTimeEntryTimer::class)->handle($viewer, $archivedTodo->id, null))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => app(DeleteTimeEntry::class)->handle($viewer, $foreignEntry))
        ->toThrow(AuthorizationException::class);
});

test('completed pomodoro sessions create one linked time entry', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-10 09:00:00', config('app.timezone')));

    try {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->work()->create();
        $todo = Todo::factory()->forProject($project)->focusCandidate()->create();
        $session = PomodoroSession::factory()->forTodo($todo)->running()->create([
            'started_at' => now(),
            'last_started_at' => now(),
            'elapsed_seconds' => 0,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-03-10 09:25:00', config('app.timezone')));

        app(CompletePomodoroSession::class)->handle($user, $session);

        $entry = TimeEntry::query()->sole();

        expect($session->refresh()->status)->toBe(PomodoroSessionStatus::Completed)
            ->and($entry->source)->toBe(TimeEntrySource::Pomodoro)
            ->and($entry->status)->toBe(TimeEntryStatus::Completed)
            ->and($entry->pomodoro_session_id)->toBe($session->id)
            ->and($entry->todo->is($todo))->toBeTrue()
            ->and($entry->project->is($project))->toBeTrue()
            ->and($entry->duration_seconds)->toBe(1500);

        app(CreatePomodoroTimeEntry::class)->handle($user, $session->refresh()->load('todo'));

        expect(TimeEntry::query()->count())->toBe(1);
    } finally {
        Carbon::setTestNow();
    }
});

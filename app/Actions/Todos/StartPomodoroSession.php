<?php

namespace App\Actions\Todos;

use App\Enums\PomodoroSessionStatus;
use App\Models\PomodoroSession;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Todos\PomodoroSessionQuery;
use App\Queries\Todos\TodoFocusQuery;
use App\Rules\Todos\PomodoroDuration;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class StartPomodoroSession
{
    public function __construct(
        private readonly PomodoroSessionQuery $sessions,
        private readonly TodoFocusQuery $focusQuery,
    ) {}

    public function handle(User $user, Todo $todo, int $durationMinutes): PomodoroSession
    {
        Gate::forUser($user)->authorize('create', PomodoroSession::class);
        Gate::forUser($user)->authorize('view', $todo);

        Validator::make(
            ['durationMinutes' => (string) $durationMinutes],
            ['durationMinutes' => ['required', 'integer', new PomodoroDuration]],
            [
                'durationMinutes.required' => __('todos.validation.pomodoro_duration'),
                'durationMinutes.integer' => __('todos.validation.pomodoro_duration'),
            ],
            ['durationMinutes' => __('todos.focus.timer.duration')],
        )->validate();

        $this->focusQuery->findFor($user, $todo->id);

        if ($this->sessions->activeFor($user) !== null) {
            throw ValidationException::withMessages([
                'durationMinutes' => __('todos.validation.pomodoro_active_session'),
            ]);
        }

        $session = new PomodoroSession;
        $session->forceFill([
            'user_id' => $user->id,
            'todo_id' => $todo->id,
            'duration_minutes' => $durationMinutes,
            'elapsed_seconds' => 0,
            'status' => PomodoroSessionStatus::Running,
            'started_at' => now(),
            'last_started_at' => now(),
            'paused_at' => null,
            'completed_at' => null,
            'abandoned_at' => null,
        ])->save();

        return $session->refresh()->load('todo');
    }
}

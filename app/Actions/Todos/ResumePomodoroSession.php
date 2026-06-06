<?php

namespace App\Actions\Todos;

use App\Enums\PomodoroSessionStatus;
use App\Models\PomodoroSession;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ResumePomodoroSession
{
    public function handle(User $user, PomodoroSession $session): PomodoroSession
    {
        Gate::forUser($user)->authorize('update', $session);

        if ($session->isRunning()) {
            return $session;
        }

        if (! $session->isPaused()) {
            throw ValidationException::withMessages([
                'session' => __('todos.validation.pomodoro_active_session_required'),
            ]);
        }

        $session->forceFill([
            'status' => PomodoroSessionStatus::Running,
            'last_started_at' => now(),
            'paused_at' => null,
        ])->save();

        return $session->refresh()->load('todo');
    }
}

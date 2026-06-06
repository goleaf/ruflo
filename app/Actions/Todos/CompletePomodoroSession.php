<?php

namespace App\Actions\Todos;

use App\Enums\PomodoroSessionStatus;
use App\Models\PomodoroSession;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class CompletePomodoroSession
{
    public function handle(User $user, PomodoroSession $session): PomodoroSession
    {
        Gate::forUser($user)->authorize('update', $session);

        if (! $session->isActive()) {
            throw ValidationException::withMessages([
                'session' => __('todos.validation.pomodoro_active_session_required'),
            ]);
        }

        $session->forceFill([
            'elapsed_seconds' => $session->accruedSeconds(),
            'status' => PomodoroSessionStatus::Completed,
            'last_started_at' => null,
            'paused_at' => null,
            'completed_at' => now(),
        ])->save();

        return $session->refresh()->load('todo');
    }
}

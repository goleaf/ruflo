<?php

namespace App\Actions\Todos;

use App\Enums\PomodoroSessionStatus;
use App\Enums\TimeEntrySource;
use App\Enums\TimeEntryStatus;
use App\Models\PomodoroSession;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class CreatePomodoroTimeEntry
{
    public function handle(User $user, PomodoroSession $session): ?TimeEntry
    {
        Gate::forUser($user)->authorize('view', $session);
        $session->loadMissing('todo');

        if ($session->status !== PomodoroSessionStatus::Completed || $session->elapsed_seconds < 60) {
            return null;
        }

        $existing = TimeEntry::query()
            ->ownedBy($user)
            ->where('pomodoro_session_id', $session->id)
            ->first();

        if ($existing instanceof TimeEntry) {
            return $existing;
        }

        return $user->timeEntries()->create([
            'todo_id' => $session->todo_id,
            'project_id' => $session->todo?->project_id,
            'pomodoro_session_id' => $session->id,
            'duration_seconds' => $session->elapsed_seconds,
            'source' => TimeEntrySource::Pomodoro,
            'status' => TimeEntryStatus::Completed,
            'entry_date' => ($session->completed_at ?? now())->toDateString(),
            'started_at' => $session->started_at,
            'stopped_at' => $session->completed_at,
            'notes' => null,
        ])->refresh()->load(['todo', 'project']);
    }
}

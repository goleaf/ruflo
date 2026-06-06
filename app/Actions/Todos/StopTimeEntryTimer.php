<?php

namespace App\Actions\Todos;

use App\Enums\TimeEntryStatus;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class StopTimeEntryTimer
{
    public function handle(User $user, TimeEntry $entry): TimeEntry
    {
        Gate::forUser($user)->authorize('update', $entry);

        if (! $entry->isRunning()) {
            throw ValidationException::withMessages([
                'timer' => __('todos.validation.time_entry_timer_required'),
            ]);
        }

        $entry->forceFill([
            'duration_seconds' => $entry->elapsedSeconds(),
            'status' => TimeEntryStatus::Completed,
            'stopped_at' => now(),
        ])->save();

        return $entry->refresh()->load(['todo', 'project']);
    }
}

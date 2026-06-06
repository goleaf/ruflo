<?php

namespace App\Actions\Todos;

use App\Enums\TimeEntrySource;
use App\Enums\TimeEntryStatus;
use App\Models\TimeEntry;
use App\Models\User;
use App\Queries\Todos\TimeEntryQuery;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class StartTimeEntryTimer
{
    public function __construct(
        private readonly TimeEntryQuery $timeEntries,
    ) {}

    public function handle(User $user, ?int $todoId, ?int $projectId): TimeEntry
    {
        Gate::forUser($user)->authorize('create', TimeEntry::class);

        if ($this->timeEntries->activeFor($user) instanceof TimeEntry) {
            throw ValidationException::withMessages([
                'timer' => __('todos.validation.time_entry_active_timer'),
            ]);
        }

        $context = $this->timeEntries->resolveContext($user, $todoId, $projectId);

        return $user->timeEntries()->create([
            'todo_id' => $context['todo']?->id,
            'project_id' => $context['project']?->id,
            'duration_seconds' => 0,
            'source' => TimeEntrySource::Timer,
            'status' => TimeEntryStatus::Running,
            'entry_date' => today()->toDateString(),
            'started_at' => now(),
            'stopped_at' => null,
            'notes' => null,
        ])->refresh()->load(['todo', 'project']);
    }
}

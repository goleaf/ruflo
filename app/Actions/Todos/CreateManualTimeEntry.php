<?php

namespace App\Actions\Todos;

use App\Data\Todos\TimeEntryData;
use App\Enums\TimeEntrySource;
use App\Enums\TimeEntryStatus;
use App\Models\TimeEntry;
use App\Models\User;
use App\Queries\Todos\TimeEntryQuery;
use Illuminate\Support\Facades\Gate;

final class CreateManualTimeEntry
{
    public function __construct(
        private readonly TimeEntryQuery $timeEntries,
    ) {}

    public function handle(User $user, TimeEntryData $data): TimeEntry
    {
        Gate::forUser($user)->authorize('create', TimeEntry::class);

        $context = $this->timeEntries->resolveContext($user, $data->todoId, $data->projectId);

        return $user->timeEntries()->create([
            'todo_id' => $context['todo']?->id,
            'project_id' => $context['project']?->id,
            'duration_seconds' => $data->durationMinutes * 60,
            'source' => TimeEntrySource::Manual,
            'status' => TimeEntryStatus::Completed,
            'entry_date' => $data->entryDate,
            'started_at' => null,
            'stopped_at' => null,
            'notes' => $data->notes,
        ])->refresh()->load(['todo', 'project']);
    }
}

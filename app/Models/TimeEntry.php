<?php

namespace App\Models;

use App\Enums\TimeEntrySource;
use App\Enums\TimeEntryStatus;
use App\Models\Concerns\BelongsToUser;
use App\Policies\TimeEntryPolicy;
use Carbon\CarbonInterface;
use Database\Factories\TimeEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['todo_id', 'project_id', 'pomodoro_session_id', 'duration_seconds', 'source', 'status', 'entry_date', 'started_at', 'stopped_at', 'notes'])]
#[UsePolicy(TimeEntryPolicy::class)]
class TimeEntry extends Model
{
    /** @use HasFactory<TimeEntryFactory> */
    use BelongsToUser, HasFactory;

    /**
     * Get the task this time entry is attached to, if any.
     *
     * @return BelongsTo<Todo, $this>
     */
    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    /**
     * Get the project this time entry contributes to, if any.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the Pomodoro session that produced this entry, if any.
     *
     * @return BelongsTo<PomodoroSession, $this>
     */
    public function pomodoroSession(): BelongsTo
    {
        return $this->belongsTo(PomodoroSession::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', TimeEntryStatus::activeValues());
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', TimeEntryStatus::Completed->value);
    }

    public function isRunning(): bool
    {
        return $this->status === TimeEntryStatus::Running;
    }

    public function isCompleted(): bool
    {
        return $this->status === TimeEntryStatus::Completed;
    }

    public function isDiscarded(): bool
    {
        return $this->status === TimeEntryStatus::Discarded;
    }

    public function elapsedSeconds(): int
    {
        $seconds = (int) $this->duration_seconds;

        if ($this->isRunning() && $this->started_at instanceof CarbonInterface) {
            $seconds += max(0, (int) $this->started_at->diffInSeconds(now()));
        }

        return $seconds;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'source' => TimeEntrySource::class,
            'status' => TimeEntryStatus::class,
            'entry_date' => 'immutable_date',
            'started_at' => 'immutable_datetime',
            'stopped_at' => 'immutable_datetime',
        ];
    }
}

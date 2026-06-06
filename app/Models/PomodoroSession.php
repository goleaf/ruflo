<?php

namespace App\Models;

use App\Enums\PomodoroSessionStatus;
use App\Models\Concerns\BelongsToUser;
use App\Policies\PomodoroSessionPolicy;
use Carbon\CarbonInterface;
use Database\Factories\PomodoroSessionFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UsePolicy(PomodoroSessionPolicy::class)]
class PomodoroSession extends Model
{
    /** @use HasFactory<PomodoroSessionFactory> */
    use BelongsToUser, HasFactory;

    /**
     * Get the task this focus session is attached to.
     *
     * @return BelongsTo<Todo, $this>
     */
    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', PomodoroSessionStatus::activeValues());
    }

    public function isRunning(): bool
    {
        return $this->status === PomodoroSessionStatus::Running;
    }

    public function isPaused(): bool
    {
        return $this->status === PomodoroSessionStatus::Paused;
    }

    public function isActive(): bool
    {
        return in_array($this->status, [PomodoroSessionStatus::Running, PomodoroSessionStatus::Paused], true);
    }

    public function durationSeconds(): int
    {
        return $this->duration_minutes * 60;
    }

    public function accruedSeconds(): int
    {
        $elapsedSeconds = (int) $this->elapsed_seconds;

        if ($this->isRunning() && $this->last_started_at instanceof CarbonInterface) {
            $elapsedSeconds += max(0, (int) $this->last_started_at->diffInSeconds(now()));
        }

        return min($elapsedSeconds, $this->durationSeconds());
    }

    public function remainingSeconds(): int
    {
        return max(0, $this->durationSeconds() - $this->accruedSeconds());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'elapsed_seconds' => 'integer',
            'status' => PomodoroSessionStatus::class,
            'started_at' => 'immutable_datetime',
            'last_started_at' => 'immutable_datetime',
            'paused_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'abandoned_at' => 'immutable_datetime',
        ];
    }
}

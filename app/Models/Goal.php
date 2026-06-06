<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Policies\GoalPolicy;
use Database\Factories\GoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'target_date'])]
#[UsePolicy(GoalPolicy::class)]
class Goal extends Model
{
    /** @use HasFactory<GoalFactory> */
    use BelongsToUser, HasFactory;

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<GoalMilestone, $this>
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(GoalMilestone::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    /**
     * Tasks directly linked to this goal.
     *
     * @return HasMany<Todo, $this>
     */
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_date' => 'immutable_date',
            'completed_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
        ];
    }
}

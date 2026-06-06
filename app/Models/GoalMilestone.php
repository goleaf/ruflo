<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Policies\GoalMilestonePolicy;
use Database\Factories\GoalMilestoneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'target_date', 'position'])]
#[UsePolicy(GoalMilestonePolicy::class)]
class GoalMilestone extends Model
{
    /** @use HasFactory<GoalMilestoneFactory> */
    use BelongsToUser, HasFactory;

    /**
     * @return BelongsTo<Goal, $this>
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * @return HasMany<Todo, $this>
     */
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_date' => 'immutable_date',
            'completed_at' => 'immutable_datetime',
        ];
    }
}

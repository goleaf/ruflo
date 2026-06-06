<?php

namespace App\Models;

use App\Enums\HabitFrequency;
use App\Models\Concerns\BelongsToUser;
use App\Policies\HabitPolicy;
use Database\Factories\HabitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'frequency', 'target_count', 'starts_on'])]
#[UsePolicy(HabitPolicy::class)]
class Habit extends Model
{
    /** @use HasFactory<HabitFactory> */
    use BelongsToUser, HasFactory;

    /**
     * @return BelongsTo<Goal, $this>
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * @return HasMany<HabitCheckIn, $this>
     */
    public function checkIns(): HasMany
    {
        return $this->hasMany(HabitCheckIn::class)
            ->orderByDesc('occurred_on')
            ->orderByDesc('id');
    }

    /**
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
            'frequency' => HabitFrequency::class,
            'target_count' => 'integer',
            'starts_on' => 'immutable_date',
            'archived_at' => 'immutable_datetime',
        ];
    }
}

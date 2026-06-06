<?php

namespace App\Models;

use App\Enums\ReminderStatus;
use App\Models\Concerns\BelongsToUser;
use App\Policies\ReminderPolicy;
use Database\Factories\ReminderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['remind_at', 'status', 'processed_at', 'skipped_at', 'skipped_reason', 'last_error'])]
#[UsePolicy(ReminderPolicy::class)]
class Reminder extends Model
{
    /** @use HasFactory<ReminderFactory> */
    use BelongsToUser, HasFactory;

    /**
     * @return BelongsTo<Todo, $this>
     */
    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class)->withTrashed();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'remind_at' => 'immutable_datetime',
            'status' => ReminderStatus::class,
            'processed_at' => 'immutable_datetime',
            'skipped_at' => 'immutable_datetime',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ReminderStatus::Pending->value);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query->pending()->where('remind_at', '<=', now());
    }

    public function isPending(): bool
    {
        return $this->status === ReminderStatus::Pending;
    }

    public function isDue(): bool
    {
        return $this->isPending() && $this->remind_at !== null && $this->remind_at->lessThanOrEqualTo(now());
    }

    public function isTaskActionable(): bool
    {
        return $this->todo instanceof Todo
            && ! $this->todo->trashed()
            && $this->todo->isActive();
    }
}

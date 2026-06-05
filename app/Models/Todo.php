<?php

namespace App\Models;

use App\Enums\TodoStatus;
use App\Models\Concerns\BelongsToUser;
use App\Policies\TodoPolicy;
use Database\Factories\TodoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A private task owned by a single user (their workspace).
 *
 * Mass assignment is restricted to user-controllable fields only. Ownership
 * (`user_id`) is never fillable and must be assigned through the owning
 * relationship in an action, never from request input.
 */
#[Fillable(['title', 'is_completed'])]
#[UsePolicy(TodoPolicy::class)]
class Todo extends Model
{
    /** @use HasFactory<TodoFactory> */
    use BelongsToUser, HasFactory, SoftDeletes;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_completed' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'archived_at' => 'immutable_datetime',
        ];
    }

    /**
     * Whether the task is archived (hidden from active/completed views).
     */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * Whether the task is currently active (not completed, not archived).
     */
    public function isActive(): bool
    {
        return ! $this->is_completed && ! $this->isArchived();
    }

    /**
     * The derived lifecycle bucket for display. Archived wins over completed.
     */
    public function status(): TodoStatus
    {
        return match (true) {
            $this->isArchived() => TodoStatus::Archived,
            $this->is_completed => TodoStatus::Completed,
            default => TodoStatus::Active,
        };
    }

    /**
     * Scope to active tasks: not completed and not archived.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at')->where('is_completed', false);
    }

    /**
     * Scope to completed tasks that are not archived.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNull('archived_at')->where('is_completed', true);
    }

    /**
     * Scope to archived tasks (regardless of completion).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }
}

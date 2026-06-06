<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Policies\TodoCommentPolicy;
use Database\Factories\TodoCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A plain-text task comment owned by the parent task's workspace.
 *
 * The owning `user_id` is the task owner, while `author_id` is the user who
 * wrote the comment. This lets shared project editors comment without
 * transferring resource ownership away from the task owner.
 */
#[Fillable(['body', 'edited_at'])]
#[UsePolicy(TodoCommentPolicy::class)]
class TodoComment extends Model
{
    /** @use HasFactory<TodoCommentFactory> */
    use BelongsToUser, HasFactory, SoftDeletes;

    /**
     * The commented task.
     *
     * @return BelongsTo<Todo, $this>
     */
    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class)->withTrashed();
    }

    /**
     * The user who wrote the comment.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Users safely resolved from mention tokens in this comment.
     *
     * @return HasMany<TodoCommentMention, $this>
     */
    public function mentions(): HasMany
    {
        return $this->hasMany(TodoCommentMention::class);
    }

    public function isAuthoredBy(User $user): bool
    {
        return (int) $this->author_id === (int) $user->id;
    }

    public function excerpt(int $limit = 160): string
    {
        return str($this->body)
            ->replace(["\r\n", "\r", "\n"], ' ')
            ->squish()
            ->limit($limit, '...')
            ->toString();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'edited_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }
}

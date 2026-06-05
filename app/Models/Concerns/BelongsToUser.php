<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shared ownership boundary for private workspace resources.
 *
 * Every todo-related model that uses this concern is owned by exactly one
 * user. The owner is the user's private workspace until a dedicated Workspace
 * model is introduced. Query scoping and ownership checks must flow through
 * this concern so the rule stays in one place and future resources (projects,
 * tags, reminders) inherit identical behavior.
 */
trait BelongsToUser
{
    /**
     * The owning user (the private workspace boundary).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to records owned by the given user.
     *
     * This is the single source of truth for ownership scoping. Never query a
     * todo-related resource without applying it (directly or via the user
     * relationship).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where($this->qualifyColumn('user_id'), $user->id);
    }

    /**
     * Determine whether the given user owns this record.
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }
}

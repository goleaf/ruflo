<?php

namespace App\Queries\Tags;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Owner-scoped read boundary for tags. Pickers and filters must source their
 * options here so one user's tag names never appear for another.
 */
final class TagListQuery
{
    /**
     * @return Builder<Tag>
     */
    public function visibleFor(User $user): Builder
    {
        return Tag::query()->ownedBy($user)->orderBy('name');
    }

    /**
     * @return Collection<int, Tag>
     */
    public function allFor(User $user): Collection
    {
        return $this->visibleFor($user)->get();
    }

    public function findVisibleFor(User $user, int $tagId): Tag
    {
        return Tag::query()->ownedBy($user)->findOrFail($tagId);
    }
}

<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Tags are private to their owner. Denials read as "not found" so a tag's
 * existence (and name) never leaks across workspaces.
 */
final class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Tag $tag): Response
    {
        return $this->ownerOnly($user, $tag);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Tag $tag): Response
    {
        return $this->ownerOnly($user, $tag);
    }

    public function delete(User $user, Tag $tag): Response
    {
        return $this->ownerOnly($user, $tag);
    }

    private function ownerOnly(User $user, Tag $tag): Response
    {
        return $tag->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}

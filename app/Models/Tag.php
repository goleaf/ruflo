<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Policies\TagPolicy;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A user-owned label that can be attached to many tasks.
 *
 * Tags are private to their owner (enforced through {@see BelongsToUser} and a
 * per-user unique name) so one user's labels never leak into another's pickers
 * or filters.
 */
#[Fillable(['name', 'color'])]
#[UsePolicy(TagPolicy::class)]
class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use BelongsToUser, HasFactory;

    /**
     * Tasks carrying this tag.
     *
     * @return BelongsToMany<Todo, $this>
     */
    public function todos(): BelongsToMany
    {
        return $this->belongsToMany(Todo::class);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Policies\TodoChecklistItemPolicy;
use Database\Factories\TodoChecklistItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A contained checklist row owned by the same user as its parent task.
 *
 * Checklist items are not standalone tasks: they inherit visibility and
 * lifecycle behavior from their parent todo. Deleting a checklist item removes
 * only that contained row, while archiving or trashing the parent task preserves
 * its checklist until the parent is restored or force-deleted.
 */
#[Fillable(['title', 'is_completed', 'position', 'completed_at'])]
#[UsePolicy(TodoChecklistItemPolicy::class)]
class TodoChecklistItem extends Model
{
    /** @use HasFactory<TodoChecklistItemFactory> */
    use BelongsToUser, HasFactory;

    /**
     * The parent task.
     *
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
            'is_completed' => 'boolean',
            'position' => 'integer',
            'completed_at' => 'immutable_datetime',
        ];
    }
}

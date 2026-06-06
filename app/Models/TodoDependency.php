<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Policies\TodoDependencyPolicy;
use Database\Factories\TodoDependencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['todo_id', 'depends_on_todo_id'])]
#[UsePolicy(TodoDependencyPolicy::class)]
class TodoDependency extends Model
{
    /** @use HasFactory<TodoDependencyFactory> */
    use BelongsToUser, HasFactory;

    /**
     * The task that is waiting for another task.
     *
     * @return BelongsTo<Todo, $this>
     */
    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    /**
     * The task blocking the waiting task.
     *
     * @return BelongsTo<Todo, $this>
     */
    public function blocker(): BelongsTo
    {
        return $this->belongsTo(Todo::class, 'depends_on_todo_id');
    }

    public function isOpen(): bool
    {
        return $this->blocker instanceof Todo && ! $this->blocker->is_completed && ! $this->blocker->trashed();
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Policies\SavedTodoViewPolicy;
use Database\Factories\SavedTodoViewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A user-owned saved task list view.
 *
 * The criteria payload stores bounded URL filter state only. Applying a saved
 * view still flows through the normal Livewire sanitizer and `TodoListQuery`,
 * so stale project/tag ids cannot widen the private list.
 */
#[Fillable(['name', 'criteria'])]
#[UsePolicy(SavedTodoViewPolicy::class)]
class SavedTodoView extends Model
{
    /** @use HasFactory<SavedTodoViewFactory> */
    use BelongsToUser, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'criteria' => 'array',
        ];
    }
}

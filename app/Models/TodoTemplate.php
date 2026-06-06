<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\TaskTemplateKind;
use App\Models\Concerns\BelongsToUser;
use App\Policies\TodoTemplatePolicy;
use Database\Factories\TodoTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A reusable, private template that can create real tasks, projects, and
 * contained checklist rows in the owner's workspace.
 */
#[Fillable(['name', 'kind', 'visibility', 'title', 'description', 'priority', 'due_offset_days', 'project_name', 'checklist_items'])]
#[UsePolicy(TodoTemplatePolicy::class)]
class TodoTemplate extends Model
{
    /** @use HasFactory<TodoTemplateFactory> */
    use BelongsToUser, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => TaskTemplateKind::class,
            'priority' => Priority::class,
            'due_offset_days' => 'integer',
            'checklist_items' => 'array',
        ];
    }

    public function isShared(): bool
    {
        return $this->visibility === 'shared';
    }
}

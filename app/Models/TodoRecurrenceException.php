<?php

namespace App\Models;

use App\Enums\RecurrenceExceptionType;
use App\Models\Concerns\BelongsToUser;
use App\Policies\TodoRecurrenceExceptionPolicy;
use Database\Factories\TodoRecurrenceExceptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['type', 'original_occurs_on', 'adjusted_occurs_on', 'note'])]
#[UsePolicy(TodoRecurrenceExceptionPolicy::class)]
class TodoRecurrenceException extends Model
{
    /** @use HasFactory<TodoRecurrenceExceptionFactory> */
    use BelongsToUser, HasFactory;

    /**
     * @return BelongsTo<TodoRecurrenceRule, $this>
     */
    public function recurrenceRule(): BelongsTo
    {
        return $this->belongsTo(TodoRecurrenceRule::class, 'todo_recurrence_rule_id');
    }

    /**
     * @return BelongsTo<Todo, $this>
     */
    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class)->withTrashed();
    }

    public function typeLabel(): string
    {
        return $this->type->label();
    }

    public function typeColor(): string
    {
        return $this->type->color();
    }

    public function typeIcon(): string
    {
        return $this->type->icon();
    }

    public function isSkipped(): bool
    {
        return $this->type === RecurrenceExceptionType::Skipped;
    }

    public function isMoved(): bool
    {
        return $this->type === RecurrenceExceptionType::Moved;
    }

    public function isEdited(): bool
    {
        return $this->type === RecurrenceExceptionType::Edited;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => RecurrenceExceptionType::class,
            'original_occurs_on' => 'immutable_date',
            'adjusted_occurs_on' => 'immutable_date',
        ];
    }
}
